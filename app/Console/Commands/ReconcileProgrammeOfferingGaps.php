<?php

namespace App\\Console\\Commands;

use Illuminate\\Console\\Command;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;
use Throwable;

/**
 * Read-only reconciliation report for the governed Step 3 offering-gap register.
 *
 * This command deliberately does not fetch remote URLs, write database records,
 * approve candidates, or change canonical data. A "candidate" classification is
 * a review queue outcome, never authorization to repair production.
 */
class ReconcileProgrammeOfferingGaps extends Command
{
    protected $signature = 'institutes:offering-gap-reconcile
        {--register=docs/step3-offering-gap-evidence-register.csv : Committed evidence register CSV}
        {--expected-rows=36 : Expected register row count; override only for isolated tests}
        {--json : Print machine-readable JSON}';

    protected $description = 'Produce a read-only, row-level reconciliation report for programme-offering gaps.';

    public function handle(): int
    {
        $startedAt = microtime(true);
        $report = [
            'check' => 'institutes_offering_gap_reconciliation',
            'checked_at' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'mode' => 'read_only',
            'mutation_performed' => false,
            'database_mutations' => 0,
            'production_write_authorization' => false,
            'register_path' => (string) $this->option('register'),
            'expected_rows' => max(0, (int) $this->option('expected-rows')),
            'summary' => [],
            'records' => [],
            'errors' => [],
        ];

        try {
            foreach (['programs', 'institutions', 'campuses', 'program_offerings'] as $table) {
                if (! Schema::hasTable($table)) {
                    $report['errors'][] = 'required_table_missing:'.$table;
                }
            }
            foreach ([
                'programs' => ['id', 'institution_id', 'name', 'status'],
                'campuses' => ['id', 'institution_id', 'name', 'status'],
                'program_offerings' => ['id', 'program_id', 'campus_id', 'delivery_mode', 'status'],
            ] as $table => $columns) {
                foreach ($columns as $column) {
                    if (Schema::hasTable($table) && ! Schema::hasColumn($table, $column)) {
                        $report['errors'][] = 'required_column_missing:'.$table.'.'.$column;
                    }
                }
            }
            if ($report['errors'] !== []) {
                return $this->finish($report, $startedAt, self::FAILURE);
            }

            $path = (string) $this->option('register');
            if (! is_file($path) || ! is_readable($path)) {
                $report['errors'][] = 'register_file_missing_or_unreadable';
                return $this->finish($report, $startedAt, self::FAILURE);
            }

            $handle = fopen($path, 'rb');
            if ($handle === false) {
                $report['errors'][] = 'register_file_unreadable';
                return $this->finish($report, $startedAt, self::FAILURE);
            }

            $header = fgetcsv($handle);
            $required = [
                'program_id', 'institution_id', 'program_name', 'official_source_url',
                'source_status', 'campus_status', 'remediation_decision',
                'candidate_campus_id', 'candidate_campus_name',
                'candidate_delivery_mode', 'campus_source_url',
            ];
            if (! is_array($header) || array_diff($required, $header) !== []) {
                fclose($handle);
                $report['errors'][] = 'register_required_columns_missing';
                return $this->finish($report, $startedAt, self::FAILURE);
            }

            $indexes = array_flip($header);
            $rows = [];
            $line = 1;
            while (($values = fgetcsv($handle)) !== false) {
                $line++;
                if ($values === [null] || $values === []) {
                    continue;
                }

                $row = [];
                foreach ($required as $field) {
                    $row[$field] = trim((string) ($values[$indexes[$field]] ?? ''));
                }
                $row['_line'] = $line;
                $rows[] = $row;
            }
            fclose($handle);

            $seenProgramIds = [];
            $seenCandidateKeys = [];
            $summary = [
                'register_rows' => count($rows),
                'expected_row_count' => $report['expected_rows'],
                'register_row_count_matches_expected' => count($rows) === $report['expected_rows'],
                'duplicate_program_ids' => 0,
                'duplicate_candidate_keys' => 0,
                'program_found_and_institution_matched' => 0,
                'campus_found_and_institution_matched' => 0,
                'active_offering_already_exists' => 0,
                'inactive_offering_already_exists' => 0,
                'offering_exists_with_different_delivery_mode' => 0,
                'offering_exists_at_other_campus' => 0,
                'missing_relationship_candidates' => 0,
                'blocked_rows' => 0,
                'review_only_rows' => 0,
            ];

            foreach ($rows as $row) {
                $pid = ctype_digit($row['program_id']) ? (int) $row['program_id'] : 0;
                $iid = ctype_digit($row['institution_id']) ? (int) $row['institution_id'] : 0;
                $cid = ctype_digit($row['candidate_campus_id']) ? (int) $row['candidate_campus_id'] : 0;
                $program = $pid > 0
                    ? DB::table('programs')->where('id', $pid)->first(['id', 'institution_id', 'name', 'status'])
                    : null;
                $campus = $cid > 0
                    ? DB::table('campuses')->where('id', $cid)->first(['id', 'institution_id', 'name', 'status'])
                    : null;

                $programMatches = $program && (int) $program->institution_id === $iid;
                $campusMatches = $campus && (int) $campus->institution_id === $iid;
                $campusNameMatches = $campus
                    && mb_strtolower(trim($row['candidate_campus_name'])) === mb_strtolower(trim((string) $campus->name));

                $duplicateProgramId = $pid > 0 && isset($seenProgramIds[$pid]);
                if ($duplicateProgramId) {
                    $summary['duplicate_program_ids']++;
                }
                if ($pid > 0) {
                    $seenProgramIds[$pid] = true;
                }

                $candidateKey = $pid.':'.$cid.':'.mb_strtolower($row['candidate_delivery_mode']);
                $duplicateCandidateKey = isset($seenCandidateKeys[$candidateKey]);
                if ($duplicateCandidateKey) {
                    $summary['duplicate_candidate_keys']++;
                }
                $seenCandidateKeys[$candidateKey] = true;

                if ($programMatches) {
                    $summary['program_found_and_institution_matched']++;
                }
                if ($campusMatches) {
                    $summary['campus_found_and_institution_matched']++;
                }

                $pairOfferings = collect();
                $programOfferings = collect();
                if ($programMatches) {
                    $programOfferings = DB::table('program_offerings')
                        ->where('program_id', $pid)
                        ->get(['id', 'program_id', 'campus_id', 'delivery_mode', 'status']);
                    $pairOfferings = $programOfferings->where('campus_id', $cid)->values();
                }
                $exactModeOfferings = $pairOfferings
                    ->filter(fn ($offering) => mb_strtolower(trim((string) $offering->delivery_mode))
                        === mb_strtolower(trim($row['candidate_delivery_mode'])))
                    ->values();
                $activeExact = $exactModeOfferings->contains(fn ($offering) => $offering->status === 'active');
                $inactiveExact = $exactModeOfferings->contains(fn ($offering) => $offering->status !== 'active');
                $differentMode = $pairOfferings->isNotEmpty() && $exactModeOfferings->isEmpty();
                $otherCampus = $programOfferings->contains(fn ($offering) => (int) $offering->campus_id !== $cid);

                if ($activeExact) {
                    $summary['active_offering_already_exists']++;
                }
                if ($inactiveExact) {
                    $summary['inactive_offering_already_exists']++;
                }
                if ($differentMode) {
                    $summary['offering_exists_with_different_delivery_mode']++;
                }
                if ($otherCampus) {
                    $summary['offering_exists_at_other_campus']++;
                }

                $officialUrlValid = $this->isSafeEvidenceUrl($row['official_source_url']);
                $campusUrlValid = $this->isSafeEvidenceUrl($row['campus_source_url']);
                $registerEvidenceReady = $row['source_status'] === 'source-found'
                    && $officialUrlValid
                    && $campusUrlValid
                    && str_contains($row['campus_status'], 'confirmed')
                    && $row['remediation_decision'] === 'ready-for-validation';

                $blockers = [];
                if ($pid < 1 || ! $programMatches) $blockers[] = 'program_missing_or_wrong_institution';
                if ($cid < 1 || ! $campusMatches) $blockers[] = 'campus_missing_or_wrong_institution';
                if (! $campusNameMatches) $blockers[] = 'candidate_campus_name_does_not_match_live_name';
                if ($duplicateProgramId) $blockers[] = 'duplicate_program_id_in_register';
                if ($duplicateCandidateKey) $blockers[] = 'duplicate_candidate_key_in_register';
                if (! $officialUrlValid) $blockers[] = 'invalid_or_missing_official_programme_url';
                if (! $campusUrlValid) $blockers[] = 'invalid_or_missing_campus_url';
                if (! $registerEvidenceReady) $blockers[] = 'register_evidence_not_confirmed_for_validation';

                if ($activeExact) {
                    $classification = 'ACTIVE_OFFERING_ALREADY_EXISTS';
                    $summary['review_only_rows']++;
                } elseif ($inactiveExact) {
                    $classification = 'INACTIVE_OFFERING_EXISTS_REQUIRES_REVIEW';
                    $summary['review_only_rows']++;
                } elseif ($differentMode) {
                    $classification = 'OFFERING_EXISTS_DELIVERY_MODE_MISMATCH_REQUIRES_REVIEW';
                    $summary['review_only_rows']++;
                } elseif ($otherCampus) {
                    $classification = 'OFFERING_EXISTS_AT_OTHER_CAMPUS_REQUIRES_REVIEW';
                    $summary['review_only_rows']++;
                } elseif ($blockers !== []) {
                    $classification = 'BLOCKED_INSUFFICIENT_OR_MISMATCHED_EVIDENCE';
                    $summary['blocked_rows']++;
                } else {
                    $classification = 'MISSING_RELATIONSHIP_CANDIDATE_REQUIRES_SOURCE_REVIEW';
                    $summary['missing_relationship_candidates']++;
                }

                $report['records'][] = [
                    'register_line' => $row['_line'],
                    'program_id' => $pid ?: null,
                    'institution_id' => $iid ?: null,
                    'program_name_register' => $row['program_name'],
                    'program_name_live' => $program->name ?? null,
                    'program_status_live' => $program->status ?? null,
                    'program_institution_matches' => (bool) $programMatches,
                    'candidate_campus_id' => $cid ?: null,
                    'candidate_campus_name_register' => $row['candidate_campus_name'],
                    'campus_name_live' => $campus->name ?? null,
                    'campus_status_live' => $campus->status ?? null,
                    'campus_institution_matches' => (bool) $campusMatches,
                    'campus_name_matches_live' => (bool) $campusNameMatches,
                    'candidate_delivery_mode' => $row['candidate_delivery_mode'],
                    'matching_program_campus_offerings' => $pairOfferings->count(),
                    'matching_delivery_mode_offerings' => $exactModeOfferings->count(),
                    'active_exact_offering_exists' => (bool) $activeExact,
                    'inactive_exact_offering_exists' => (bool) $inactiveExact,
                    'different_delivery_mode_exists' => (bool) $differentMode,
                    'offering_at_other_campus_exists' => (bool) $otherCampus,
                    'official_source_url' => $row['official_source_url'],
                    'campus_source_url' => $row['campus_source_url'],
                    'source_url_syntax_valid' => (bool) $officialUrlValid,
                    'campus_url_syntax_valid' => (bool) $campusUrlValid,
                    'register_evidence_ready_for_validation' => (bool) $registerEvidenceReady,
                    'classification' => $classification,
                    'blockers' => $blockers,
                    'proposed_action' => $this->proposedAction($classification),
                    'canonical_mutation_performed' => false,
                ];
            }

            $report['summary'] = $summary;
            $report['reconciliation_status'] = $summary['register_row_count_matches_expected']
                ? 'ROW_LEVEL_REPORT_CREATED_REVIEW_REQUIRED'
                : 'BLOCKED_REGISTER_ROW_COUNT_MISMATCH';
            $report['limitations'] = [
                'Database reads only; no INSERT, UPDATE, DELETE, promotion, approval, or publication.',
                'URL checks validate syntax only; they do not prove the page exists or supports the claim.',
                'The committed register is a hypothesis/evidence pointer, not canonical truth.',
                'Every proposed action requires official-source review and a separately approved repair plan.',
            ];

            return $this->finish($report, $startedAt, self::SUCCESS);
        } catch (Throwable $e) {
            $report['errors'][] = 'reconciliation_failed:'.class_basename($e);
            return $this->finish($report, $startedAt, self::FAILURE);
        }
    }

    private function proposedAction(string $classification): string
    {
        return match ($classification) {
            'ACTIVE_OFFERING_ALREADY_EXISTS' => 'verify_source_and_close_gap_as_already_present',
            'INACTIVE_OFFERING_EXISTS_REQUIRES_REVIEW' => 'review_existing_record_and_status_no_automatic_reactivation',
            'OFFERING_EXISTS_DELIVERY_MODE_MISMATCH_REQUIRES_REVIEW' => 'verify_delivery_mode_against_official_source',
            'OFFERING_EXISTS_AT_OTHER_CAMPUS_REQUIRES_REVIEW' => 'verify_campus_mapping_before_any_change',
            'BLOCKED_INSUFFICIENT_OR_MISMATCHED_EVIDENCE' => 'collect_or_correct_evidence_and_re-run_read_only_audit',
            default => 'prepare_row_level_repair_proposal_after_official_source_review',
        };
    }

    private function isSafeEvidenceUrl(string $url): bool
    {
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        return is_array($parts)
            && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && ! isset($parts['user'])
            && ! isset($parts['pass']);
    }

    private function finish(array $report, float $startedAt, int $exitCode): int
    {
        $report['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
        $report['database_mutations'] = 0;
        $report['mutation_performed'] = false;

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['Metric', 'Value'],
                collect($report['summary'] ?? [])->map(fn ($value, $key) => [$key, $value])->values()->all()
            );
            $this->info('reconciliation_status='.($report['reconciliation_status'] ?? 'FAILED'));
            $this->line('database_mutations=0');
        }

        return $exitCode;
    }
}

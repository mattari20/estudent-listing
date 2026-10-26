<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class OfferingGapPreflight extends Command
{
    protected $signature = 'institutes:offering-gap-preflight
        {--register=docs/step3-offering-gap-evidence-register.csv : Path to the committed evidence CSV}
        {--json : Print machine-readable JSON}';

    protected $description = 'Read-only live database preflight for Step 3 programme offering gaps.';

    public function handle(): int
    {
        $startedAt = microtime(true);
        $report = [
            'check' => 'institutes_offering_gap_preflight',
            'checked_at' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'mode' => 'read_only',
            'mutation_performed' => false,
            'database_mutations' => 0,
            'production_write_authorization' => false,
            'register_path' => (string) $this->option('register'),
            'summary' => [],
            'records' => [],
            'errors' => [],
        ];

        try {
            foreach (['programs', 'institutions', 'campuses', 'program_offerings'] as $table) {
                if (!Schema::hasTable($table)) {
                    $report['errors'][] = "required_table_missing:{$table}";
                }
            }
            if ($report['errors']) {
                return $this->finish($report, $startedAt, self::FAILURE);
            }

            $path = (string) $this->option('register');
            if (!is_file($path)) {
                $report['errors'][] = 'register_file_missing';
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
            if (!is_array($header) || array_diff($required, $header)) {
                fclose($handle);
                $report['errors'][] = 'register_required_columns_missing';
                return $this->finish($report, $startedAt, self::FAILURE);
            }
            $indexes = array_flip($header);
            $rows = [];
            $line = 1;
            while (($values = fgetcsv($handle)) !== false) {
                $line++;
                if ($values === [null] || $values === []) continue;
                $row = [];
                foreach ($required as $field) {
                    $row[$field] = trim((string) ($values[$indexes[$field]] ?? ''));
                }
                $row['_line'] = $line;
                $rows[] = $row;
            }
            fclose($handle);

            $seen = [];
            $seenProgramIds = [];
            $summary = [
                'register_rows' => count($rows),
                'program_found' => 0,
                'program_missing_or_institution_mismatch' => 0,
                'campus_found' => 0,
                'campus_missing_or_institution_mismatch' => 0,
                'existing_active_offering' => 0,
                'duplicate_offering_exists' => 0,
                'duplicate_program_id_in_register' => 0,
                'evidence_blocked' => 0,
                'preflight_ready_for_manual_review' => 0,
                'blocked' => 0,
            ];

            foreach ($rows as $row) {
                $pid = ctype_digit($row['program_id']) ? (int) $row['program_id'] : 0;
                $iid = ctype_digit($row['institution_id']) ? (int) $row['institution_id'] : 0;
                $cid = ctype_digit($row['candidate_campus_id']) ? (int) $row['candidate_campus_id'] : 0;
                $program = $pid ? DB::table('programs')->where('id', $pid)->first(['id', 'institution_id', 'name', 'status']) : null;
                $campusColumns = ['id', 'institution_id', 'name', 'status'];
                foreach (['slug', 'address', 'geography_id', 'campus_type'] as $optionalColumn) {
                    if (Schema::hasColumn('campuses', $optionalColumn)) $campusColumns[] = $optionalColumn;
                }
                $campus = $cid ? DB::table('campuses')->where('id', $cid)->first($campusColumns) : null;
                $programOk = $program && (int) $program->institution_id === $iid;
                $campusOk = $campus && (int) $campus->institution_id === $iid;
                $activeOffering = false;
                $anyOffering = false;
                $duplicateKey = $pid . ':' . $cid . ':' . $row['candidate_delivery_mode'];
                $duplicateProgramId = $pid > 0 && isset($seenProgramIds[$pid]);
                if ($duplicateProgramId) $summary['duplicate_program_id_in_register']++;
                if ($pid > 0) $seenProgramIds[$pid] = true;

                if ($programOk) $summary['program_found']++;
                else $summary['program_missing_or_institution_mismatch']++;
                if ($campusOk) $summary['campus_found']++;
                else $summary['campus_missing_or_institution_mismatch']++;

                if ($programOk && $cid > 0 && Schema::hasColumn('program_offerings', 'delivery_mode')) {
                    $base = DB::table('program_offerings')->where('program_id', $pid)->where('campus_id', $cid);
                    $anyOffering = (clone $base)->exists();
                    $activeOffering = (clone $base)->where('status', 'active')->exists();
                    if ($activeOffering) $summary['existing_active_offering']++;
                    if ($anyOffering) $summary['duplicate_offering_exists']++;
                }

                $officialEvidenceUrlValid = $this->isSafeEvidenceUrl($row['official_source_url']);
                $campusEvidenceUrlValid = $this->isSafeEvidenceUrl($row['campus_source_url']);
                $evidenceOk = $row['source_status'] === 'source-found'
                    && $officialEvidenceUrlValid
                    && $campusEvidenceUrlValid
                    && str_contains($row['campus_status'], 'confirmed')
                    && $row['remediation_decision'] === 'ready-for-validation';
                $blockers = [];
                if (!$programOk) $blockers[] = 'program_missing_or_wrong_institution';
                if (!$campusOk) $blockers[] = 'campus_missing_or_wrong_institution';
                $candidateCampusNameMatchesLive = $campus
                    && mb_strtolower(trim($row['candidate_campus_name'])) === mb_strtolower(trim((string) $campus->name));
                if (!$candidateCampusNameMatchesLive) $blockers[] = 'candidate_campus_name_does_not_match_live_name';
                if (str_contains($row['campus_status'], 'wrong-campus')) $blockers[] = 'register_marks_wrong_campus';
                if (!$officialEvidenceUrlValid) $blockers[] = 'invalid_official_programme_evidence_url';
                if (!$campusEvidenceUrlValid) $blockers[] = 'invalid_campus_evidence_url';
                if (!$evidenceOk) $blockers[] = 'official_programme_and_campus_evidence_not_confirmed';
                if ($activeOffering) $blockers[] = 'active_offering_already_exists';
                if ($anyOffering) $blockers[] = 'offering_row_already_exists';
                if (isset($seen[$duplicateKey])) $blockers[] = 'duplicate_candidate_in_register';
                if ($duplicateProgramId) $blockers[] = 'duplicate_program_id_in_register';
                $seen[$duplicateKey] = true;

                $ready = $blockers === [];
                if ($ready) $summary['preflight_ready_for_manual_review']++;
                else {
                    $summary['blocked']++;
                    if (!$evidenceOk) $summary['evidence_blocked']++;
                }

                $report['records'][] = [
                    'program_id' => $pid ?: null,
                    'institution_id' => $iid ?: null,
                    'register_line' => $row['_line'],
                    'programme_name_register' => $row['program_name'],
                    'official_source_url' => $row['official_source_url'],
                    'source_status_register' => $row['source_status'],
                    'campus_status_register' => $row['campus_status'],
                    'remediation_decision_register' => $row['remediation_decision'],
                    'campus_source_url' => $row['campus_source_url'],
                    'programme_found' => (bool) $program,
                    'programme_institution_matches' => (bool) $programOk,
                    'programme_status_live' => $program->status ?? null,
                    'campus_id_candidate' => $cid ?: null,
                    'campus_found' => (bool) $campus,
                    'campus_institution_matches' => (bool) $campusOk,
                    'campus_name_candidate' => $row['candidate_campus_name'],
                    'campus_name_live' => $campus->name ?? null,
                    'candidate_campus_name_matches_live' => (bool) $candidateCampusNameMatchesLive,
                    'campus_slug_live' => $campus->slug ?? null,
                    'campus_address_live' => $campus->address ?? null,
                    'campus_geography_id_live' => isset($campus->geography_id) ? (int) $campus->geography_id : null,
                    'campus_type_live' => $campus->campus_type ?? null,
                    'campus_status_live' => $campus->status ?? null,
                    'candidate_delivery_mode' => $row['candidate_delivery_mode'],
                    'any_offering_exists_for_program_campus' => (bool) $anyOffering,
                    'active_offering_exists_for_program_campus' => (bool) $activeOffering,
                    'official_evidence_url_valid' => (bool) $officialEvidenceUrlValid,
                    'campus_evidence_url_valid' => (bool) $campusEvidenceUrlValid,
                    'evidence_gate_passed' => (bool) $evidenceOk,
                    'preflight_result' => $ready ? 'READY_FOR_MANUAL_REVIEW_ONLY' : 'BLOCKED',
                    'blockers' => $blockers,
                ];
            }

            $report['summary'] = $summary;
            $report['preflight_status'] = ($summary['blocked'] === 0 && $summary['register_rows'] === 36)
                ? 'ALL_ROWS_READY_FOR_MANUAL_REVIEW_ONLY'
                : 'BLOCKED_ROWS_REMAIN';
            $report['limitations'] = [
                'This command performs SELECT queries only.',
                'Candidate campus IDs and delivery modes are not treated as verified merely because they appear in the register.',
                'Ready for manual review is not authorization to insert or activate production offerings.',
            ];
            return $this->finish($report, $startedAt, self::SUCCESS);
        } catch (Throwable $e) {
            // Preserve useful diagnostics without exposing SQL, hostnames, bindings, or credentials.
            $sqlState = null;
            if ($e instanceof \Illuminate\Database\QueryException) {
                $sqlState = $e->errorInfo[0] ?? $e->getCode();
            }
            $safeSqlState = $sqlState ? preg_replace('/[^A-Za-z0-9]/', '', (string) $sqlState) : '';
            $report['errors'][] = 'preflight_failed:' . class_basename($e) . ($safeSqlState !== '' ? ':SQLSTATE_' . $safeSqlState : '');
            return $this->finish($report, $startedAt, self::FAILURE);
        }
    }


    private function isSafeEvidenceUrl(string $url): bool
    {
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        if (! is_array($parts)) {
            return false;
        }

        return in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
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
                collect($report['summary'] ?? [])->map(fn ($v, $k) => [$k, $v])->values()->all()
            );
            $this->info('preflight_status=' . ($report['preflight_status'] ?? 'FAILED'));
            $this->line('database_mutations=0');
        }
        return $exitCode;
    }
}

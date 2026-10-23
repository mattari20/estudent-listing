<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DataGapRecordAudit extends Command
{
    protected $signature = 'institutes:data-gap-record-audit {--json : Print the audit as JSON} {--summary : Print aggregate counts only and omit row-level records}';

    protected $description = 'Produce a read-only record-level audit for targeted data-gap remediation.';

    public function handle(): int
    {
        $startedAt = microtime(true);
        $report = [
            'check' => 'institutes_data_gap_record_audit',
            'checked_at' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'scope' => 'read_only_private_record_level_audit',
            'mutation_performed' => false,
            'sections' => [],
            'limitations' => [
                'This audit does not establish external source truth or prove that an institution is missing from the directory.',
                'Candidate and rejected import rows require human review; their payloads are deliberately excluded.',
                'The report does not change, activate, deactivate, import, promote, or publish records.',
            ],
        ];

        try {
            foreach (['institutions', 'campuses', 'programs', 'import_batches', 'import_rows'] as $table) {
                if (! Schema::hasTable($table)) {
                    $report['sections']['schema'] = [
                        'status' => 'critical',
                        'missing_table' => $table,
                    ];

                    return $this->finish($report, $startedAt, self::FAILURE);
                }
            }

            $institutions = DB::table('institutions')
                ->select(['id', 'name', 'slug', 'institution_type', 'status'])
                ->orderBy('id')
                ->get();

            $campusCounts = DB::table('campuses')
                ->select('institution_id', DB::raw('COUNT(*) as total'))
                ->groupBy('institution_id')
                ->pluck('total', 'institution_id');

            $programCounts = DB::table('programs')
                ->select('institution_id', DB::raw('COUNT(*) as total'))
                ->groupBy('institution_id')
                ->pluck('total', 'institution_id');

            $report['sections']['institutions'] = [
                'total' => $institutions->count(),
                'records' => $institutions->map(fn ($row): array => [
                    'id' => (int) $row->id,
                    'name' => $row->name,
                    'slug' => $row->slug,
                    'institution_type' => $row->institution_type,
                    'status' => $row->status,
                    'campus_count' => (int) ($campusCounts[$row->id] ?? 0),
                    'program_count' => (int) ($programCounts[$row->id] ?? 0),
                ])->all(),
            ];

            $report['sections']['programs'] = [
                'total' => DB::table('programs')->count(),
                'records' => DB::table('programs')
                    ->select(['id', 'institution_id', 'name', 'slug', 'program_level', 'status'])
                    ->orderBy('id')
                    ->get()
                    ->map(fn ($row): array => [
                        'id' => (int) $row->id,
                        'institution_id' => (int) $row->institution_id,
                        'name' => $row->name,
                        'slug' => $row->slug,
                        'program_level' => $row->program_level,
                        'status' => $row->status,
                    ])->all(),
            ];

            $batchColumns = array_values(array_filter(
                ['id', 'batch_key', 'entity_type', 'source_type', 'source_id', 'status', 'total_rows', 'valid_rows', 'invalid_rows', 'candidate_rows', 'received_at', 'completed_at'],
                fn (string $column): bool => Schema::hasColumn('import_batches', $column),
            ));

            $report['sections']['import_batches'] = [
                'total' => DB::table('import_batches')->count(),
                'records' => DB::table('import_batches')
                    ->select($batchColumns)
                    ->orderBy('id')
                    ->get()
                    ->map(fn ($row): array => (array) $row)->all(),
            ];

            $rowColumns = array_values(array_filter(
                ['id', 'import_batch_id', 'row_number', 'status', 'match_state', 'canonical_subject_type', 'canonical_subject_id', 'parent_subject_type', 'parent_subject_id', 'validation_errors'],
                fn (string $column): bool => Schema::hasColumn('import_rows', $column),
            ));

            $rows = DB::table('import_rows')
                ->leftJoin('import_batches', 'import_batches.id', '=', 'import_rows.import_batch_id')
                ->select(array_map(fn (string $column): string => 'import_rows.'.$column, $rowColumns))
                ->addSelect('import_batches.entity_type as entity_type')
                ->orderBy('import_rows.import_batch_id')
                ->orderBy('import_rows.row_number')
                ->get();

            $rowRecords = $rows->map(function ($row): array {
                $record = (array) $row;
                $validationErrors = $record['validation_errors'] ?? null;
                unset($record['validation_errors']);

                if (is_string($validationErrors)) {
                    $validationErrors = json_decode($validationErrors, true);
                }

                $record['validation_error_count'] = is_array($validationErrors)
                    ? array_sum(array_map(
                        fn ($messages): int => is_array($messages) ? count($messages) : (is_string($messages) && trim($messages) !== '' ? 1 : 0),
                        $validationErrors,
                    ))
                    : 0;
                $record['validation_error_fields'] = is_array($validationErrors) ? array_keys($validationErrors) : [];
                $record['has_notes'] = Schema::hasColumn('import_rows', 'notes')
                    && DB::table('import_rows')->where('id', $record['id'])->whereNotNull('notes')->where('notes', '<>', '')->exists();

                $record['has_approved_promotion_decision'] = Schema::hasTable('import_promotion_decisions')
                    && DB::table('import_promotion_decisions')
                        ->where('import_row_id', $record['id'])
                        ->where('decision', 'approved')
                        ->exists();

                $latestEvent = Schema::hasTable('import_promotion_events')
                    ? DB::table('import_promotion_events')
                        ->where('import_row_id', $record['id'])
                        ->orderByDesc('occurred_at')
                        ->orderByDesc('id')
                        ->first(['event_type', 'metadata', 'reason'])
                    : null;
                $record['latest_promotion_event_type'] = $latestEvent->event_type ?? null;
                $metadata = isset($latestEvent->metadata) ? json_decode((string) $latestEvent->metadata, true) : [];
                $record['latest_promotion_error_class'] = is_array($metadata) ? ($metadata['error_class'] ?? null) : null;
                $record['latest_promotion_error_category'] = in_array(
                    $record['latest_promotion_event_type'],
                    ['promotion_failed', 'promotion_error'],
                    true,
                ) ? $this->classifyPromotionFailure((string) ($latestEvent->reason ?? '')) : null;
                $record['has_active_source_evidence'] = Schema::hasTable('assertions') && Schema::hasTable('evidence')
                    && DB::table('assertions')
                        ->where('subject_type', \App\Models\ImportRow::class)
                        ->where('subject_id', $record['id'])
                        ->whereExists(fn ($query) => $query
                            ->selectRaw('1')
                            ->from('evidence')
                            ->whereColumn('evidence.id', 'assertions.evidence_id')
                            ->where('evidence.status', 'active'))
                        ->exists();
                $record['canonical_institution_status'] = ($record['canonical_subject_type'] ?? null) === \App\Models\Institution::class
                    && ! empty($record['canonical_subject_id'])
                    ? DB::table('institutions')->where('id', $record['canonical_subject_id'])->value('status')
                    : null;

                // A non-destructive recommendation helps reviewers prioritize rows.
                // It never authorizes deletion, promotion, or publication.
                $record['review_reasons'] = [];
                $rowStatus = (string) ($record['status'] ?? '');
                $eventType = (string) ($record['latest_promotion_event_type'] ?? '');

                if ($rowStatus === 'promoted') {
                    $record['review_disposition'] = 'retain_promoted_history';
                    $record['review_reasons'][] = 'Row is already promoted; retain its audit history.';
                    if (! $record['has_active_source_evidence']) {
                        $record['review_reasons'][] = 'Active source evidence is missing; keep publication blocked until verified evidence is recorded.';
                    }
                    if (($record['canonical_institution_status'] ?? null) !== null && $record['canonical_institution_status'] !== 'active') {
                        $record['review_reasons'][] = 'The canonical institution is not active; complete the explicit publication workflow only after all gates pass.';
                    }
                } elseif ($rowStatus === 'duplicate' || ($record['match_state'] ?? null) === 'duplicate') {
                    $record['review_disposition'] = 'verify_duplicate_link';
                    $record['review_reasons'][] = 'Verify the canonical match and dependency links before any archival decision.';
                } elseif ($record['validation_error_count'] > 0) {
                    $record['review_disposition'] = 'correct_validation_errors';
                    $record['review_reasons'][] = 'Validation errors are recorded; inspect the private validation details before retrying.';
                } elseif ($rowStatus === 'rejected' || $eventType === 'promotion_rejected') {
                    $record['review_disposition'] = 'review_rejection';
                    $record['review_reasons'][] = 'Preserve the rejection audit trail and inspect the decision/event reason before reprocessing.';
                } elseif (in_array($eventType, ['promotion_failed', 'promotion_error'], true)) {
                    $record['review_disposition'] = 'investigate_promotion_failure';
                    $record['review_reasons'][] = 'A promotion failure event exists; inspect its private details before retrying.';
                } elseif (! ($record['has_approved_promotion_decision'] ?? false)) {
                    $record['review_disposition'] = 'review_promotion_decision';
                    $record['review_reasons'][] = 'No approved promotion decision is recorded; do not promote or publish automatically.';
                } else {
                    $record['review_disposition'] = 'manual_review';
                    $record['review_reasons'][] = 'Available audit fields do not establish a safe automated disposition.';
                }

                return $record;
            })->all();

            $report['sections']['import_rows'] = [
                'total' => $rows->count(),
                'by_status' => $rows->groupBy(fn ($row) => (string) ($row->status ?? 'null'))
                    ->map(fn ($items) => $items->count())->all(),
                'records' => $rowRecords,
                'excluded_fields' => ['payload', 'normalized_payload', 'notes', 'full validation error text', 'full promotion event reason'],
            ];

            $report['sections']['campuses'] = [
                'total' => DB::table('campuses')->count(),
                'by_status' => Schema::hasColumn('campuses', 'status')
                    ? DB::table('campuses')->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->get()
                        ->mapWithKeys(fn ($row): array => [(string) ($row->status ?? 'null') => (int) $row->total])->all()
                    : [],
            ];

            $report['status'] = 'complete';
        } catch (Throwable $exception) {
            $report['status'] = 'critical';
            $report['error_type'] = class_basename($exception);
        }

        return $this->finish($report, $startedAt, ($report['status'] ?? '') === 'critical' ? self::FAILURE : self::SUCCESS);
    }

    private function classifyPromotionFailure(string $reason): string
    {
        $reason = trim($reason);

        if ($reason === '') {
            return 'unclassified';
        }

        if (preg_match('/Unsupported enrichment entity type:/i', $reason) === 1) {
            return 'unsupported_entity_type';
        }

        if (preg_match('/(?:require|requires|must have|must reference).{0,80}parent|parent.{0,80}(?:required|missing|invalid)/i', $reason) === 1) {
            return 'missing_or_invalid_parent';
        }

        if (preg_match('/SQLSTATE|unknown column|column not found|database error|integrity constraint|SQL error/i', $reason) === 1) {
            return 'database_or_schema_error';
        }

        if (preg_match('/not found|does not exist|missing dependency|no query results/i', $reason) === 1) {
            return 'missing_dependency';
        }

        return 'other_runtime_exception';
    }

    private function finish(array $report, float $startedAt, int $exitCode): int
    {
        $report['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);

        if ($this->option('summary') && isset($report['sections']['import_rows'])) {
            $sections = $report['sections'];
            $dispositions = [];
            $failureCategories = [];
            foreach (($sections['import_rows']['records'] ?? []) as $record) {
                $disposition = (string) ($record['review_disposition'] ?? 'unclassified');
                $dispositions[$disposition] = ($dispositions[$disposition] ?? 0) + 1;
                $failureCategory = $record['latest_promotion_error_category'] ?? null;
                if (is_string($failureCategory) && $failureCategory !== '') {
                    $failureCategories[$failureCategory] = ($failureCategories[$failureCategory] ?? 0) + 1;
                }
            }
            ksort($dispositions);
            ksort($failureCategories);

            $report['summary_only'] = true;
            $report['sections'] = [
                'institutions' => ['total' => $sections['institutions']['total'] ?? null],
                'programs' => ['total' => $sections['programs']['total'] ?? null],
                'import_batches' => ['total' => $sections['import_batches']['total'] ?? null],
                'import_rows' => [
                    'total' => $sections['import_rows']['total'] ?? null,
                    'by_status' => $sections['import_rows']['by_status'] ?? [],
                    'by_review_disposition' => $dispositions,
                    'promotion_failure_categories' => $failureCategories,
                ],
                'campuses' => [
                    'total' => $sections['campuses']['total'] ?? null,
                    'by_status' => $sections['campuses']['by_status'] ?? [],
                ],
            ];
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['Section', 'Count'],
                collect($report['sections'] ?? [])->map(fn ($value, $key) => [
                    $key,
                    is_array($value) ? ($value['total'] ?? 'reported') : 'reported',
                ])->all(),
            );
            $this->line('Read-only record audit; mutation performed: no');
        }

        return $exitCode;
    }
}

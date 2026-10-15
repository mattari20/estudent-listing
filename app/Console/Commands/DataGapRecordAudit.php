<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DataGapRecordAudit extends Command
{
    protected $signature = 'institutes:data-gap-record-audit {--json : Print the private record-level audit as JSON}';

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
                ['id', 'import_batch_id', 'row_number', 'status', 'match_state', 'canonical_subject_type', 'canonical_subject_id', 'parent_subject_type', 'parent_subject_id'],
                fn (string $column): bool => Schema::hasColumn('import_rows', $column),
            ));

            $rows = DB::table('import_rows')
                ->select($rowColumns)
                ->orderBy('import_batch_id')
                ->orderBy('row_number')
                ->get();

            $report['sections']['import_rows'] = [
                'total' => $rows->count(),
                'by_status' => $rows->groupBy(fn ($row) => (string) ($row->status ?? 'null'))
                    ->map(fn ($items) => $items->count())->all(),
                'records' => $rows->map(fn ($row): array => (array) $row)->all(),
                'excluded_fields' => ['payload', 'normalized_payload', 'notes', 'full validation error text'],
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

    private function finish(array $report, float $startedAt, int $exitCode): int
    {
        $report['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);

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

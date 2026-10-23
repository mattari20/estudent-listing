<?php

namespace App\Console\Commands;

use App\Services\GeographyGapDetectionService;
use App\Services\GeographyIntegrityService;
use App\Services\InstituteDataReadinessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DataGapReport extends Command
{
    protected $signature = 'institutes:data-gap-report {--json : Print the report as JSON}';

    protected $description = 'Produce a read-only directory data-gap report for operator review.';

    public function handle(
        InstituteDataReadinessService $readiness,
        GeographyIntegrityService $geographyIntegrity,
        GeographyGapDetectionService $geographyGaps,
    ): int {
        $startedAt = microtime(true);
        $report = [
            'check' => 'institutes_data_gap_report',
            'checked_at' => now()->toIso8601String(),
            'status' => 'healthy',
            'scope' => 'read_only_production_data_diagnosis',
            'mutation_performed' => false,
            'environment' => app()->environment(),
            'sections' => [],
            'limitations' => [
                'Counts describe the configured database at check time.',
                'A zero count is not by itself proof of a defect; compare it with the approved data-coverage requirements.',
                'This report does not verify the live deployment commit or external source truth.',
                'This report does not create, update, delete, activate, deactivate, import, promote, or publish records.',
            ],
        ];

        try {
            DB::connection()->select('SELECT 1');
            $report['sections']['database'] = [
                'status' => 'healthy',
                'driver' => DB::connection()->getDriverName(),
            ];
        } catch (Throwable $exception) {
            $report['status'] = 'critical';
            $report['sections']['database'] = [
                'status' => 'critical',
                'error_type' => class_basename($exception),
            ];

            return $this->finish($report, $startedAt, self::FAILURE);
        }

        $requiredTables = [
            'geographies', 'institutions', 'campuses', 'programs',
            'program_offerings', 'admission_intakes',
        ];
        $missingTables = array_values(array_filter(
            $requiredTables,
            fn (string $table): bool => ! Schema::hasTable($table),
        ));

        $report['sections']['schema'] = [
            'status' => $missingTables === [] ? 'healthy' : 'critical',
            'required_tables' => $requiredTables,
            'missing_tables' => $missingTables,
        ];

        if ($missingTables !== []) {
            $report['status'] = 'critical';

            return $this->finish($report, $startedAt, self::FAILURE);
        }

        try {
            $summary = $readiness->summarize();
            $report['sections']['canonical_population'] = $summary['population'];
            $report['sections']['canonical_relationship_gaps'] = $summary['integrity'];
            $report['sections']['canonical_relationship_gaps_total'] = $summary['blocking_findings'];

            $geographyCounts = DB::table('geographies')
                ->select('type', 'status', DB::raw('COUNT(*) as total'))
                ->groupBy('type', 'status')
                ->orderBy('type')
                ->orderBy('status')
                ->get()
                ->map(fn ($row): array => [
                    'type' => $row->type,
                    'status' => $row->status,
                    'count' => (int) $row->total,
                ])->all();

            $report['sections']['geography'] = [
                'total' => array_sum(array_column($geographyCounts, 'count')),
                'by_type_and_status' => $geographyCounts,
                'integrity' => $geographyIntegrity->validate(),
                'active_campuses_with_missing_or_inactive_geography' => $geographyGaps->campuses()->count(),
            ];

            $duplicateInstitutionNames = DB::table('institutions')
                ->selectRaw('LOWER(TRIM(name)) as normalized_name')
                ->whereNotNull('name')
                ->whereRaw("TRIM(name) <> ''")
                ->groupByRaw('LOWER(TRIM(name))')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count();

            $report['sections']['duplicate_candidates'] = [
                'institution_name_groups_after_trim_and_case_fold' => $duplicateInstitutionNames,
                'note' => 'Candidate groups only; automated matching does not establish that records are duplicates.',
            ];

            $report['sections']['coverage'] = $this->coverageSummary();

            $report['sections']['import_pipeline'] = $this->tableCounts([
                'import_batches' => ['total' => null, 'by_status' => 'status'],
                'import_rows' => ['total' => null, 'by_status' => 'status'],
                'import_conflicts' => ['total' => null, 'by_status' => 'status'],
                'enrichment_import_rows' => ['total' => null, 'by_status' => 'status'],
                'import_promotion_decisions' => ['total' => null, 'by_status' => 'status'],
            ]);

            $report['sections']['source_evidence'] = $this->tableCounts([
                'sources' => ['total' => null, 'by_status' => 'status'],
                'evidence' => ['total' => null, 'by_status' => 'status'],
                'source_snapshots' => ['total' => null, 'by_status' => 'status'],
                'source_monitoring_policies' => ['total' => null, 'by_status' => 'status'],
                'source_retrieval_runs' => ['total' => null, 'by_status' => 'status'],
            ]);

            $hasWarnings = $summary['blocking_findings'] > 0
                || $duplicateInstitutionNames > 0
                || ! ($report['sections']['geography']['integrity']['valid'] ?? false)
                || $report['sections']['geography']['active_campuses_with_missing_or_inactive_geography'] > 0;

            foreach (['import_pipeline', 'source_evidence'] as $section) {
                foreach ($report['sections'][$section] as $tableReport) {
                    if (($tableReport['availability'] ?? 'available') !== 'available') {
                        $hasWarnings = true;
                    }
                }
            }

            if ($hasWarnings) {
                $report['status'] = 'warning';
            }
        } catch (Throwable $exception) {
            $report['status'] = 'critical';
            $report['sections']['report_execution'] = [
                'status' => 'critical',
                'error_type' => class_basename($exception),
            ];
        }

        return $this->finish(
            $report,
            $startedAt,
            $report['status'] === 'critical' ? self::FAILURE : self::SUCCESS,
        );
    }

    private function coverageSummary(): array
    {
        $groupCounts = static function (string $table, array $columns): array {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    return [];
                }
            }

            return DB::table($table)
                ->select(array_merge($columns, [DB::raw('COUNT(*) as total')]))
                ->groupBy(...$columns)
                ->orderBy(...$columns)
                ->get()
                ->map(function ($row) use ($columns): array {
                    $record = [];
                    foreach ($columns as $column) {
                        $record[$column] = $row->{$column};
                    }
                    $record['count'] = (int) $row->total;

                    return $record;
                })->all();
        };

        $campusTotal = DB::table('campuses')->count();
        $campusWithGeography = Schema::hasColumn('campuses', 'geography_id')
            ? DB::table('campuses')->whereNotNull('geography_id')->count()
            : null;
        $activeCampusWithoutGeography = Schema::hasColumn('campuses', 'geography_id') && Schema::hasColumn('campuses', 'status')
            ? DB::table('campuses')->where('status', 'active')->whereNull('geography_id')->count()
            : null;

        return [
            'institutions_by_type_and_status' => $groupCounts('institutions', ['institution_type', 'status']),
            'programs_by_level_and_status' => $groupCounts('programs', ['program_level', 'status']),
            'program_offerings_by_delivery_mode_and_status' => $groupCounts('program_offerings', ['delivery_mode', 'status']),
            'admission_intakes_by_status' => $groupCounts('admission_intakes', ['status']),
            'campus_geography_coverage' => [
                'total_campuses' => $campusTotal,
                'campuses_with_geography' => $campusWithGeography,
                'campuses_without_geography' => $campusWithGeography === null ? null : $campusTotal - $campusWithGeography,
                'active_campuses_without_geography' => $activeCampusWithoutGeography,
            ],
            'institutions_without_campuses' => DB::table('institutions')
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('campuses')->whereColumn('campuses.institution_id', 'institutions.id'))
                ->count(),
            'institutions_without_programs' => DB::table('institutions')
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('programs')->whereColumn('programs.institution_id', 'institutions.id'))
                ->count(),
            'programs_without_offerings' => DB::table('programs')
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('program_offerings')->whereColumn('program_offerings.program_id', 'programs.id'))
                ->count(),
        ];
    }

    private function tableCounts(array $tables): array
    {
        $results = [];

        foreach ($tables as $table => $configuration) {
            if (! Schema::hasTable($table)) {
                $results[$table] = [
                    'availability' => 'missing_table',
                    'total' => null,
                    'by_status' => [],
                ];
                continue;
            }

            $result = [
                'availability' => 'available',
                'total' => DB::table($table)->count(),
                'by_status' => [],
            ];

            $statusColumn = $configuration['by_status'] ?? null;
            if ($statusColumn !== null && Schema::hasColumn($table, $statusColumn)) {
                $result['by_status'] = DB::table($table)
                    ->select($statusColumn, DB::raw('COUNT(*) as total'))
                    ->groupBy($statusColumn)
                    ->orderBy($statusColumn)
                    ->get()
                    ->mapWithKeys(fn ($row): array => [
                        (string) ($row->{$statusColumn} ?? 'null') => (int) $row->total,
                    ])->all();
            }

            $results[$table] = $result;
        }

        return $results;
    }

    private function finish(array $report, float $startedAt, int $exitCode): int
    {
        $report['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $rows = [];
            foreach ($report['sections'] as $section => $value) {
                $rows[] = [$section, is_array($value) && isset($value['status']) ? $value['status'] : 'reported'];
            }
            $this->table(['Section', 'Status'], $rows);
            $this->line('Overall status: '.$report['status']);
            $this->line('Read-only report; mutation performed: no');
        }

        return $exitCode;
    }
}

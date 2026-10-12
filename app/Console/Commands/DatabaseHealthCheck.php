<?php

namespace App\\Console\\Commands;

use App\\Services\\GeographyGapDetectionService;
use App\\Services\\GeographyIntegrityService;
use App\\Services\\InstituteDataReadinessService;
use Illuminate\\Console\\Command;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;
use Throwable;

class DatabaseHealthCheck extends Command
{
    protected $signature = 'institutes:database-health {--json : Print the report as JSON}';

    protected $description = 'Run read-only database connectivity, schema, and directory integrity checks.';

    public function handle(
        InstituteDataReadinessService $readiness,
        GeographyIntegrityService $geographyIntegrity,
        GeographyGapDetectionService $geographyGaps,
    ): int {
        $startedAt = microtime(true);
        $report = [
            'check' => 'institutes_database_health',
            'checked_at' => now()->toIso8601String(),
            'status' => 'healthy',
            'checks' => [],
            'summary' => [],
            'mutation_performed' => false,
        ];

        try {
            DB::connection()->select('SELECT 1');
            $report['checks']['database_connection'] = [
                'status' => 'healthy',
                'driver' => DB::connection()->getDriverName(),
            ];
        } catch (Throwable $exception) {
            $report['checks']['database_connection'] = [
                'status' => 'critical',
                'error_type' => class_basename($exception),
            ];
            $report['status'] = 'critical';

            return $this->finish($report, $startedAt, self::FAILURE);
        }

        $requiredTables = [
            'geographies',
            'institutions',
            'campuses',
            'programs',
            'program_offerings',
            'admission_intakes',
            'migrations',
        ];
        $missingTables = [];

        foreach ($requiredTables as $table) {
            try {
                if (! Schema::hasTable($table)) {
                    $missingTables[] = $table;
                }
            } catch (Throwable $exception) {
                $missingTables[] = $table;
            }
        }

        $report['checks']['required_tables'] = [
            'status' => $missingTables === [] ? 'healthy' : 'critical',
            'required_count' => count($requiredTables),
            'missing' => $missingTables,
        ];

        if ($missingTables !== []) {
            $report['status'] = 'critical';

            return $this->finish($report, $startedAt, self::FAILURE);
        }

        try {
            $report['summary']['data_readiness'] = $readiness->summarize();
            $report['summary']['geography_integrity'] = $geographyIntegrity->validate();
            $report['summary']['campuses_with_geography_gaps'] = $geographyGaps->campuses()->count();

            $readinessIssues = (int) ($report['summary']['data_readiness']['blocking_findings'] ?? 0);
            $geographyValid = (bool) ($report['summary']['geography_integrity']['valid'] ?? false);
            $geographyGapCount = (int) $report['summary']['campuses_with_geography_gaps'];

            $report['checks']['directory_integrity'] = [
                'status' => $readinessIssues === 0 && $geographyValid && $geographyGapCount === 0
                    ? 'healthy'
                    : 'warning',
            ];

            if ($report['checks']['directory_integrity']['status'] === 'warning') {
                $report['status'] = 'warning';
            }
        } catch (Throwable $exception) {
            $report['checks']['directory_integrity'] = [
                'status' => 'critical',
                'error_type' => class_basename($exception),
            ];
            $report['status'] = 'critical';
        }

        return $this->finish(
            $report,
            $startedAt,
            $report['status'] === 'critical' ? self::FAILURE : self::SUCCESS,
        );
    }

    private function finish(array $report, float $startedAt, int $exitCode): int
    {
        $report['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['Check', 'Status'],
                collect($report['checks'])
                    ->map(fn (array $check, string $name): array => [$name, $check['status']])
                    ->values()
                    ->all(),
            );
            $this->line('Overall status: '.$report['status']);
            $this->line('Read-only check; mutation performed: no');
        }

        return $exitCode;
    }
}

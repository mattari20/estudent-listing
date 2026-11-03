<?php

namespace App\Console\Commands;

use App\Services\ExecutionLeaseService;
use App\Services\MonitoringDryRunService;
use App\Services\SourceMonitoringBatchService;
use Illuminate\Console\Command;

class MonitorSourceChanges extends Command
{
    protected $signature = 'institutes:monitor-changes {--policy= : Optional monitoring policy ID} {--dry-run : Show sources without retrieving them}';

    protected $description = 'Retrieve monitored institute sources and process detected content changes.';

    public function handle(
        ExecutionLeaseService $lease,
        MonitoringDryRunService $dryRun,
        SourceMonitoringBatchService $monitoring,
    ): int {
        $policyId = $this->option('policy') ? (int) $this->option('policy') : null;

        if ($this->option('dry-run')) {
            $summary = $dryRun->run($policyId);
            $this->table(['Metric', 'Value'], [
                ['policies', $summary['policies']],
                ['sources', $summary['sources']],
            ]);

            return self::SUCCESS;
        }

        $result = $lease->run('governed-institute-discovery-pipeline', function ($heartbeat) use ($monitoring, $policyId): int {
            $summary = $monitoring->run($policyId, $heartbeat);
            $this->table(['Metric', 'Value'], collect($summary)->map(fn ($value, $key) => [$key, $value])->values()->all());

            return self::SUCCESS;
        });

        if ($result === null) {
            $this->warn('Governed institute discovery is already running; this invocation did no work.');

            return self::SUCCESS;
        }

        return $result;
    }
}

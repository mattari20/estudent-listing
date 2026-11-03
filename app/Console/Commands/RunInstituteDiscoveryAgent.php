<?php

namespace App\Console\Commands;

use App\Services\ExecutionLeaseService;
use App\Services\InstituteDiscoveryAgentService;
use Illuminate\Console\Command;

class RunInstituteDiscoveryAgent extends Command
{
    protected $signature = 'institutes:discover
        {profile=cui-lahore : Discovery profile to execute}
        {--ai : Use the configured AI extraction adapter instead of the deterministic extractor}';

    protected $description = 'Collect governed institute data from a configured source and create non-canonical candidate rows.';

    public function handle(InstituteDiscoveryAgentService $agent, ExecutionLeaseService $lease): int
    {
        $result = $lease->run('governed-institute-discovery-pipeline', function ($heartbeat) use ($agent): int {
            $heartbeat();
            try {
                $summary = $agent->run(
                    $this->argument('profile'),
                    (bool) $this->option('ai')
                );
            } catch (\Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            $heartbeat();
            $this->table(
                ['Metric', 'Value'],
                collect($summary)->map(fn ($value, $key) => [$key, $value])->values()->all()
            );

            $this->info('Discovery completed. No canonical institute/program record was changed.');

            return self::SUCCESS;
        });

        if ($result === null) {
            $this->warn('Governed institute discovery is already running; this invocation did no work.');

            return self::SUCCESS;
        }

        return $result;
    }
}

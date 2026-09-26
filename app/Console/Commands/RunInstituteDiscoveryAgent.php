<?php

namespace App\Console\Commands;

use App\Services\InstituteDiscoveryAgentService;
use Illuminate\Console\Command;

class RunInstituteDiscoveryAgent extends Command
{
    protected $signature = 'institutes:discover {profile=cui-lahore : Discovery profile to execute}';

    protected $description = 'Collect governed institute data from a configured source and create non-canonical candidate rows.';

    public function handle(InstituteDiscoveryAgentService $agent): int
    {
        try {
            $summary = $agent->run($this->argument('profile'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Metric', 'Value'],
            collect($summary)->map(fn ($value, $key) => [$key, $value])->values()->all()
        );

        $this->info('Discovery completed. No canonical institute/program record was changed.');

        return self::SUCCESS;
    }
}

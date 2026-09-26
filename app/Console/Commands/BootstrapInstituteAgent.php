<?php

namespace App\Console\Commands;

use App\Models\Source;
use App\Models\SourceSnapshot;
use App\Services\DiscoveryMonitoringPolicyService;
use App\Services\InstituteDiscoveryAgentService;
use Illuminate\Console\Command;

class BootstrapInstituteAgent extends Command
{
    protected $signature = 'institutes:bootstrap-agent
        {--profile=* : Optional discovery profile(s) to bootstrap}
        {--no-discovery : Register sources and monitoring policies without the initial discovery run}';

    protected $description = 'Register configured discovery sources and perform a one-time governed bootstrap discovery when needed.';

    public function handle(
        DiscoveryMonitoringPolicyService $monitoring,
        InstituteDiscoveryAgentService $agent,
    ): int {
        $profiles = $this->option('profile');

        if ($profiles === []) {
            $profiles = array_keys(config('institute_agent.profiles', []));
        }

        $bootstrapped = 0;
        $discovered = 0;
        $skipped = 0;

        foreach ($profiles as $profile) {
            $definition = config("institute_agent.profiles.{$profile}");

            if (! is_array($definition)) {
                $this->warn("Unknown institute discovery profile: {$profile}");
                continue;
            }

            if (($definition['monitoring']['enabled'] ?? true) !== true) {
                $skipped++;
                continue;
            }

            $sourceDefinition = $definition['source'] ?? [];

            $source = Source::query()->updateOrCreate(
                ['url' => $sourceDefinition['url'] ?? null],
                [
                    'source_type' => $sourceDefinition['type'] ?? 'official',
                    'name' => $sourceDefinition['name'] ?? $profile,
                    'authority_scope' => $sourceDefinition['authority_scope'] ?? null,
                    'status' => 'active',
                ]
            );

            $monitoring->ensure($source, $profile);
            $bootstrapped++;

            if ($this->option('no-discovery')) {
                continue;
            }

            $hasSnapshot = SourceSnapshot::query()
                ->where('source_id', $source->id)
                ->exists();

            if ($hasSnapshot) {
                $skipped++;
                continue;
            }

            try {
                $summary = $agent->run($profile, (bool) ($definition['monitoring']['use_ai'] ?? false));
                $discovered++;
                $this->info(
                    $profile.' bootstrap discovery created '
                    .($summary['candidate_rows'] ?? 0).' candidate row(s).'
                );
            } catch (\Throwable $e) {
                $this->error($profile.' bootstrap discovery failed: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['profiles_bootstrapped', $bootstrapped],
                ['initial_discoveries', $discovered],
                ['skipped', $skipped],
            ]
        );

        return self::SUCCESS;
    }
}

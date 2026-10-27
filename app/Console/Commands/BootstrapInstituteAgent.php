<?php

namespace App\Console\Commands;

use App\Models\ImportBatch;
use App\Models\Institution;
use App\Models\Source;
use App\Models\SourceSnapshot;
use App\Services\DiscoveryMonitoringPolicyService;
use App\Services\InstituteDiscoveryAgentService;
use App\Services\InstituteDiscoveryCandidateExtractor;
use App\Services\InstituteIdentityDiscoveryService;
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
        InstituteIdentityDiscoveryService $identityDiscovery,
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

            $sourceDefinition = $definition['source'] ?? [];
            $sourceUrl = $sourceDefinition['url'] ?? null;

            if (($definition['monitoring']['enabled'] ?? true) !== true) {
                $monitoring->disableForProfile($profile, is_string($sourceUrl) ? $sourceUrl : null);
                $skipped++;
                continue;
            }

            // A missing canonical parent is a profile-level readiness blocker, not a
            // fatal workflow error. Disable any previously active policy rather than
            // letting a stale scheduled policy continue running against a blocked profile.
            $parentSlug = $definition['institution_slug'] ?? null;
            if (is_string($parentSlug) && $parentSlug !== ''
                && ! Institution::query()->where('slug', $parentSlug)->exists()) {
                $monitoring->disableForProfile($profile, is_string($sourceUrl) ? $sourceUrl : null);
                $this->warn(
                    $profile.' blocked: required parent institution "'.$parentSlug
                    .'" is missing from canonical data; the profile policy is disabled and no discovery was run.'
                );
                $skipped++;
                continue;
            }

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

            // Populated batches are terminal for bootstrap. Empty outcomes are
            // terminal only for the extractor version that produced them, so a
            // parser improvement can retry an obsolete zero-candidate result once.
            $latestBatch = ImportBatch::query()
                ->where('source_id', $source->id)
                ->whereIn('source_type', ['automated_discovery', 'ai_extraction'])
                ->latest('id')
                ->first();

            if ($latestBatch) {
                $hasRows = (int) $latestBatch->total_rows > 0
                    || (int) $latestBatch->rows()->count() > 0;
                $currentVersionMarker = 'discovery_extractor_version='
                    .InstituteDiscoveryCandidateExtractor::VERSION;

                if ($hasRows || str_contains((string) $latestBatch->notes, $currentVersionMarker)) {
                    $skipped++;
                    continue;
                }
            }

            try {
                if (($definition['entity_type'] ?? 'program') === 'institution') {
                    $summary = $identityDiscovery->run($profile, $source, $definition);
                } else {
                    $snapshot = SourceSnapshot::query()
                        ->where('source_id', $source->id)
                        ->latest('captured_at')
                        ->first();

                    $summary = $snapshot
                        ? $agent->runFromSnapshot(
                            $profile,
                            $snapshot,
                            (bool) ($definition['monitoring']['use_ai'] ?? false)
                        )
                        : $agent->run(
                            $profile,
                            (bool) ($definition['monitoring']['use_ai'] ?? false)
                        );
                }

                $discovered++;
                $details = ' batch #'.($summary['batch_id'] ?? 'unavailable')
                    .'; private review package '.data_get($summary, 'review_package.manifest_path', 'not-created');
                $this->info(
                    $profile.' bootstrap discovery created '
                    .($summary['candidate_rows'] ?? 0).' candidate row(s).'.$details
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

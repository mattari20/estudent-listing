<?php

namespace App\Services;

use App\Models\Source;
use App\Models\SourceMonitoringPolicy;

class DiscoveryMonitoringPolicyService
{
    public function ensure(Source $source, string $profile): SourceMonitoringPolicy
    {
        $definition = config("institute_agent.profiles.{$profile}.monitoring", []);
        $name = 'Automated discovery — '.$profile;

        // Profiles may move from a blocked/unreachable URL to a different
        // verified official source. Disable the old profile policy before
        // activating the current source so it cannot repeatedly fetch stale URLs.
        SourceMonitoringPolicy::query()
            ->where('name', $name)
            ->where('attribute', 'automated_discovery')
            ->where('source_id', '!=', $source->id)
            ->where('status', '!=', 'disabled')
            ->update(['status' => 'disabled']);

        return SourceMonitoringPolicy::query()->updateOrCreate(
            ['source_id' => $source->id, 'attribute' => 'automated_discovery'],
            [
                'name' => $name,
                'subject_type' => config("institute_agent.profiles.{$profile}.entity_type", 'program'),
                'fresh_after_days' => (int) ($definition['fresh_after_days'] ?? 7),
                'stale_after_days' => (int) ($definition['stale_after_days'] ?? 30),
                'priority' => $definition['priority'] ?? 'normal',
                'status' => ($definition['enabled'] ?? true) ? 'active' : 'disabled',
                'cadence' => $definition['cadence'] ?? 'weekly',
            ]
        );
    }

    public function disableForProfile(string $profile, ?string $sourceUrl = null): int
    {
        $name = 'Automated discovery — '.$profile;
        $query = SourceMonitoringPolicy::query()
            ->where('name', $name)
            ->where('attribute', 'automated_discovery')
            ->where('status', '!=', 'disabled');

        if (is_string($sourceUrl) && $sourceUrl !== '') {
            $sourceId = Source::query()->where('url', $sourceUrl)->value('id');
            if ($sourceId !== null) {
                $query->where('source_id', (int) $sourceId);
            }
        }

        return $query->update(['status' => 'disabled']);
    }
}

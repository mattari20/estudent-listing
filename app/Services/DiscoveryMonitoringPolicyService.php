<?php

namespace App\Services;

use App\Models\Source;
use App\Models\SourceMonitoringPolicy;

class DiscoveryMonitoringPolicyService
{
    public function ensure(Source $source, string $profile): SourceMonitoringPolicy
    {
        $definition = config("institute_agent.profiles.{$profile}.monitoring", []);

        return SourceMonitoringPolicy::query()->updateOrCreate(
            ['source_id' => $source->id, 'attribute' => 'automated_discovery'],
            [
                'name' => 'Automated discovery — '.$profile,
                'subject_type' => 'program',
                'fresh_after_days' => (int) ($definition['fresh_after_days'] ?? 7),
                'stale_after_days' => (int) ($definition['stale_after_days'] ?? 30),
                'priority' => $definition['priority'] ?? 'normal',
                'status' => ($definition['enabled'] ?? true) ? 'active' : 'disabled',
                'cadence' => $definition['cadence'] ?? 'weekly',
            ]
        );
    }
}

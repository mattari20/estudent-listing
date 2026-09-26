<?php

namespace App\Services;

use App\Models\SourceChangeObservation;

class SourceChangeDiscoveryService
{
    public function process(SourceChangeObservation $observation): ?array
    {
        if ($observation->change_state !== 'changed') {
            return null;
        }

        $observation->loadMissing('currentSnapshot');

        $snapshot = $observation->currentSnapshot;
        if (! $snapshot || ! is_string($snapshot->content) || $snapshot->content === '') {
            return null;
        }

        $profile = config('institute_agent.profiles');
        foreach ($profile as $profileKey => $definition) {
            if (($definition['source']['url'] ?? null) !== optional($observation->source)->url) {
                continue;
            }

            if (! (($definition['monitoring']['auto_discovery'] ?? true))) {
                return null;
            }

            $useAi = (bool) ($definition['monitoring']['use_ai'] ?? false);

            return app(InstituteDiscoveryAgentService::class)
                ->runFromSnapshot($profileKey, $snapshot, $useAi);
        }

        return null;
    }
}

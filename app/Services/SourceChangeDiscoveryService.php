<?php

namespace App\Services;

use App\Models\InstituteDiscoveryRunOutcome;
use App\Models\SourceChangeObservation;
use Throwable;

class SourceChangeDiscoveryService
{
    public function process(SourceChangeObservation $observation): array
    {
        $observation->loadMissing(['source', 'currentSnapshot']);
        $snapshot = $observation->currentSnapshot;
        $base = [
            'source_id' => $observation->source_id,
            'source_change_observation_id' => $observation->id,
            'snapshot_id' => $snapshot?->id,
            'occurred_at' => now(),
        ];

        if ($observation->change_state !== 'changed') {
            return $this->record($base + [
                'outcome' => 'no_change',
                'details' => ['change_state' => $observation->change_state],
            ]);
        }

        if (! $snapshot || ! is_string($snapshot->content) || trim($snapshot->content) === '') {
            return $this->record($base + [
                'outcome' => 'empty_snapshot',
                'details' => ['reason' => 'changed_observation_has_no_usable_snapshot_content'],
            ]);
        }

        foreach (config('institute_agent.profiles', []) as $profileKey => $definition) {
            if (($definition['source']['url'] ?? null) !== $observation->source?->url) {
                continue;
            }

            $identity = app(OfficialSourceIdentityVerifier::class)->verify($observation->source, [
                $profileKey => $definition,
            ]);
            if (! $identity['verified']) {
                return $this->record($base + [
                    'profile_key' => $profileKey,
                    'outcome' => 'source_identity_blocked',
                    'details' => ['blockers' => $identity['blockers']],
                ]);
            }

            if (! ($definition['monitoring']['auto_discovery'] ?? true)) {
                return $this->record($base + [
                    'profile_key' => $profileKey,
                    'outcome' => 'discovery_disabled',
                ]);
            }

            $useAi = (bool) ($definition['monitoring']['use_ai'] ?? false);
            try {
                $summary = app(InstituteDiscoveryAgentService::class)
                    ->runFromSnapshot($profileKey, $snapshot, $useAi);
            } catch (Throwable $exception) {
                $this->record($base + [
                    'profile_key' => $profileKey,
                    'outcome' => 'failed',
                    'failure_class' => get_class($exception),
                    'details' => ['message_omitted' => true],
                ]);

                throw $exception;
            }

            $package = $summary['review_package'] ?? [];
            $extractedCount = (int) ($summary['total_rows'] ?? 0);
            $candidateCount = (int) ($summary['candidate_rows'] ?? 0);
            $duplicateCount = (int) ($summary['duplicate_rows'] ?? 0);
            $outcomeName = $candidateCount > 0
                ? 'candidates_created'
                : ($extractedCount > 0 && $duplicateCount === $extractedCount
                    ? 'duplicates_only'
                    : 'empty_candidates');
            $runOutcome = $this->record($base + [
                'profile_key' => $profileKey,
                'outcome' => $outcomeName,
                'candidate_count' => $candidateCount,
                'review_package_status' => $package['status'] ?? null,
                'details' => [
                    'batch_id' => $summary['batch_id'] ?? null,
                    'extracted_rows' => $extractedCount,
                    'duplicate_rows' => $duplicateCount,
                    'used_ai' => $useAi,
                ],
            ]);

            // Preserve the discovery-agent summary contract for existing callers.
            return $summary + ['run_outcome' => $runOutcome];
        }

        return $this->record($base + [
            'outcome' => 'no_matching_profile',
            'details' => ['reason' => 'no_configured_profile_matches_source_url'],
        ]);
    }

    private function record(array $attributes): array
    {
        $outcome = InstituteDiscoveryRunOutcome::query()->create($attributes);

        return [
            'outcome' => $outcome->outcome,
            'outcome_id' => $outcome->id,
            'candidate_count' => $outcome->candidate_count,
            'review_package_status' => $outcome->review_package_status,
        ];
    }
}

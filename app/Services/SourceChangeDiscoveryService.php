<?php

namespace App\Services;

use App\Models\ImportBatch;
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

            $isInstitution = ($definition['entity_type'] ?? 'program') === 'institution';
            $useAi = (bool) ($definition['monitoring']['use_ai'] ?? false);
            $extractorVersion = $isInstitution
                ? InstituteIdentityDiscoveryService::VERSION
                : InstituteDiscoveryCandidateExtractor::VERSION;

            $existing = $this->existingOutcome($observation, (string) $profileKey, $useAi, $extractorVersion);
            if ($existing && $existing->outcome !== 'failed') {
                return $this->summaryForExistingOutcome($existing);
            }

            $identity = app(OfficialSourceIdentityVerifier::class)->verify($observation->source, [
                $profileKey => $definition,
            ]);
            if (! $identity['verified']) {
                return $this->record($base + [
                    'profile_key' => $profileKey,
                    'outcome' => 'source_identity_blocked',
                    'details' => ['blockers' => $identity['blockers']],
                ], $useAi, $extractorVersion);
            }

            if (! ($definition['monitoring']['auto_discovery'] ?? true)) {
                return $this->record($base + [
                    'profile_key' => $profileKey,
                    'outcome' => 'discovery_disabled',
                ], $useAi, $extractorVersion);
            }

            try {
                if ($isInstitution) {
                    $summary = app(InstituteIdentityDiscoveryService::class)
                        ->runFromSnapshot($profileKey, $snapshot, $definition);
                } else {
                    $summary = app(InstituteDiscoveryAgentService::class)
                        ->runFromSnapshot($profileKey, $snapshot, $useAi);
                }
            } catch (Throwable $exception) {
                $this->record($base + [
                    'profile_key' => $profileKey,
                    'outcome' => 'failed',
                    'failure_class' => get_class($exception),
                    'details' => ['message_omitted' => true],
                ], $useAi, $extractorVersion);

                throw $exception;
            }

            $batchId = isset($summary['batch_id']) ? (int) $summary['batch_id'] : 0;
            if ($batchId < 1) {
                throw new \RuntimeException('Discovery returned no durable candidate batch reference.');
            }
            $this->assertCompletedBatch($batchId);

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
            ], $useAi, $extractorVersion);

            return $summary + ['run_outcome' => $runOutcome];
        }

        return $this->record($base + [
            'outcome' => 'no_matching_profile',
            'details' => ['reason' => 'no_configured_profile_matches_source_url'],
        ]);
    }

    private function existingOutcome(
        SourceChangeObservation $observation,
        string $profileKey,
        bool $useAi,
        string $extractorVersion,
    ): ?InstituteDiscoveryRunOutcome {
        return InstituteDiscoveryRunOutcome::query()
            ->where('idempotency_key', $this->keyFor(
                (int) $observation->id,
                $profileKey,
                $useAi,
                $extractorVersion
            ))
            ->first();
    }

    private function record(array $attributes, bool $useAi = false, string $extractorVersion = 'none'): array
    {
        $key = $this->keyFor(
            (int) ($attributes['source_change_observation_id'] ?? 0),
            (string) ($attributes['profile_key'] ?? 'none'),
            $useAi,
            $extractorVersion
        );
        $attributes['idempotency_key'] = $key;

        $outcome = InstituteDiscoveryRunOutcome::query()->firstOrCreate(
            ['idempotency_key' => $key],
            $attributes
        );

        if ($outcome->outcome === 'failed' && ($attributes['outcome'] ?? null) !== 'failed') {
            $outcome->update($attributes);
        }

        return [
            'outcome' => $outcome->outcome,
            'outcome_id' => $outcome->id,
            'candidate_count' => $outcome->candidate_count,
            'review_package_status' => $outcome->review_package_status,
        ];
    }

    private function summaryForExistingOutcome(InstituteDiscoveryRunOutcome $outcome): array
    {
        $details = $outcome->details ?? [];
        if (in_array($outcome->outcome, ['candidates_created', 'duplicates_only', 'empty_candidates'], true)) {
            $batchId = (int) ($details['batch_id'] ?? 0);
            if ($batchId < 1) {
                throw new \RuntimeException('A completed discovery outcome has no durable candidate batch reference.');
            }
            $this->assertCompletedBatch($batchId);
        }

        return [
            'profile' => $outcome->profile_key,
            'batch_id' => $details['batch_id'] ?? null,
            'total_rows' => (int) ($details['extracted_rows'] ?? 0),
            'candidate_rows' => (int) $outcome->candidate_count,
            'duplicate_rows' => (int) ($details['duplicate_rows'] ?? 0),
            'run_outcome' => [
                'outcome' => $outcome->outcome,
                'outcome_id' => $outcome->id,
                'candidate_count' => $outcome->candidate_count,
                'review_package_status' => $outcome->review_package_status,
            ],
        ];
    }

    private function assertCompletedBatch(int $batchId): void
    {
        $batch = ImportBatch::query()->find($batchId);
        if (! $batch || $batch->status !== 'validated' || $batch->completed_at === null) {
            throw new \RuntimeException(
                'Discovery outcome references a missing or incomplete candidate batch; completion cannot be reported.'
            );
        }
    }

    private function keyFor(int $observationId, string $profileKey, bool $useAi, string $extractorVersion): string
    {
        return hash('sha256', implode('|', [
            'discovery-outcome:v1',
            $observationId,
            $profileKey,
            $useAi ? 'ai' : 'deterministic',
            $extractorVersion,
        ]));
    }
}

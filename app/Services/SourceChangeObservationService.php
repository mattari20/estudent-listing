<?php

namespace App\Services;

use App\Models\Source;
use App\Models\SourceChangeObservation;
use App\Models\SourceSnapshot;
use App\Models\SourceSnapshotReconciliationRequest;
use Illuminate\Support\Facades\DB;

class SourceChangeObservationService
{
    public function compare(SourceSnapshot $current): SourceChangeObservation
    {
        return DB::transaction(function () use ($current): SourceChangeObservation {
            // Captures and comparisons share this source-row lock. The predecessor
            // is therefore selected from a stable per-source chronological set.
            Source::query()->lockForUpdate()->findOrFail($current->source_id);
            $current = SourceSnapshot::query()
                ->where('source_id', $current->source_id)
                ->findOrFail($current->id);

            $previous = $this->predecessor($current);

            if (! $previous) {
                $attributes = [
                    'source_id' => $current->source_id,
                    'previous_snapshot_id' => null,
                    'change_state' => 'baseline',
                    'materiality' => 'unknown',
                    'status' => 'detected',
                    'summary' => 'Initial snapshot captured; no previous snapshot exists.',
                    'observed_at' => now(),
                ];
            } elseif (blank($previous->content_hash) || blank($current->content_hash)) {
                $attributes = [
                    'source_id' => $current->source_id,
                    'previous_snapshot_id' => $previous->id,
                    'change_state' => 'uncomparable',
                    'materiality' => 'unknown',
                    'status' => 'detected',
                    'summary' => 'Snapshot comparison is inconclusive because one or both content hashes are missing.',
                    'details' => [
                        'comparison' => 'inconclusive',
                        'previous' => $this->snapshotEvidence($previous),
                        'current' => $this->snapshotEvidence($current),
                        'previous_hash_present' => filled($previous->content_hash),
                        'current_hash_present' => filled($current->content_hash),
                    ],
                    'observed_at' => now(),
                ];
            } else {
                $same = hash_equals((string) $previous->content_hash, (string) $current->content_hash);
                $attributes = [
                    'source_id' => $current->source_id,
                    'previous_snapshot_id' => $previous->id,
                    'change_state' => $same ? 'unchanged' : 'changed',
                    'materiality' => $same ? 'none' : 'unknown',
                    'status' => 'detected',
                    'summary' => $same
                        ? 'Snapshot content hash is unchanged.'
                        : 'Snapshot content hash changed; materiality requires classification.',
                    'details' => [
                        'comparison' => $same ? 'unchanged' : 'changed',
                        'previous' => $this->snapshotEvidence($previous),
                        'current' => $this->snapshotEvidence($current),
                    ],
                    'observed_at' => now(),
                ];
            }

            $observation = SourceChangeObservation::query()->firstOrCreate(
                ['current_snapshot_id' => $current->id],
                $attributes
            );

            $this->recordChronologyMismatches($current);

            return $observation;
        }, 3);
    }

    private function predecessor(SourceSnapshot $current): ?SourceSnapshot
    {
        return SourceSnapshot::query()
            ->where('source_id', $current->source_id)
            ->where(function ($query) use ($current): void {
                $query->where('captured_at', '<', $current->captured_at)
                    ->orWhere(function ($tie) use ($current): void {
                        $tie->where('captured_at', '=', $current->captured_at)
                            ->where('id', '<', $current->id);
                    });
            })
            ->orderByDesc('captured_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Existing observations are immutable history. If a late snapshot changes
     * the expected predecessor of an already-observed snapshot, persist a
     * deterministic review request instead of rewriting observations or
     * downstream events/queues.
     */
    private function recordChronologyMismatches(SourceSnapshot $triggeringSnapshot): void
    {
        $snapshots = SourceSnapshot::query()
            ->where('source_id', $triggeringSnapshot->source_id)
            ->orderBy('captured_at')
            ->orderBy('id')
            ->get(['id', 'source_id', 'captured_at']);

        $observations = SourceChangeObservation::query()
            ->whereIn('current_snapshot_id', $snapshots->modelKeys())
            ->get()
            ->keyBy('current_snapshot_id');

        $previousId = null;

        foreach ($snapshots as $snapshot) {
            $existing = $observations->get($snapshot->id);

            if ($existing && (int) ($existing->previous_snapshot_id ?? 0) !== (int) ($previousId ?? 0)) {
                $identity = hash('sha256', json_encode([
                    (int) $snapshot->id,
                    $previousId === null ? null : (int) $previousId,
                    $existing->previous_snapshot_id === null ? null : (int) $existing->previous_snapshot_id,
                ], JSON_THROW_ON_ERROR));

                SourceSnapshotReconciliationRequest::query()->firstOrCreate(
                    ['identity_key' => $identity],
                    [
                        'source_id' => $triggeringSnapshot->source_id,
                        'triggering_snapshot_id' => $triggeringSnapshot->id,
                        'current_snapshot_id' => $snapshot->id,
                        'expected_previous_snapshot_id' => $previousId,
                        'recorded_previous_snapshot_id' => $existing->previous_snapshot_id,
                        'status' => 'pending_review',
                        'details' => [
                            'reason' => 'late_snapshot_changed_chronological_predecessor',
                            'triggering_snapshot_id' => (int) $triggeringSnapshot->id,
                            'current_snapshot_captured_at' => $snapshot->captured_at?->toISOString(),
                        ],
                    ]
                );
            }

            $previousId = $snapshot->id;
        }
    }

    /**
     * @return array<string, int|string|null>
     */
    private function snapshotEvidence(SourceSnapshot $snapshot): array
    {
        return [
            'snapshot_id' => (int) $snapshot->id,
            'reference' => $snapshot->reference,
            'content_hash' => $snapshot->content_hash,
            'content_length' => $snapshot->content_length,
            'content_type' => $snapshot->content_type,
            'captured_at' => $snapshot->captured_at?->toISOString(),
            'status' => $snapshot->status,
        ];
    }
}

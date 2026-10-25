<?php

namespace App\Services;

use App\Models\SourceChangeObservation;
use App\Models\SourceSnapshot;

class SourceChangeObservationService
{
    public function compare(SourceSnapshot $current): SourceChangeObservation
    {
        $existing = SourceChangeObservation::query()
            ->where('current_snapshot_id', $current->id)
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $previous = SourceSnapshot::query()
            ->where('source_id', $current->source_id)
            ->where('id', '!=', $current->id)
            ->latest('captured_at')
            ->first();

        if (! $previous) {
            return SourceChangeObservation::create([
                'source_id' => $current->source_id,
                'current_snapshot_id' => $current->id,
                'change_state' => 'baseline',
                'materiality' => 'unknown',
                'status' => 'detected',
                'summary' => 'Initial snapshot captured; no previous snapshot exists.',
                'observed_at' => now(),
            ]);
        }

        if (blank($previous->content_hash) || blank($current->content_hash)) {
            return SourceChangeObservation::create([
                'source_id' => $current->source_id,
                'previous_snapshot_id' => $previous->id,
                'current_snapshot_id' => $current->id,
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
            ]);
        }

        $same = hash_equals((string) $previous->content_hash, (string) $current->content_hash);

        return SourceChangeObservation::create([
            'source_id' => $current->source_id,
            'previous_snapshot_id' => $previous->id,
            'current_snapshot_id' => $current->id,
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
        ]);
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

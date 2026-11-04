<?php

namespace App\Services;

use App\Models\IdentityResolutionDecision;
use App\Models\ImportConflict;
use App\Models\ImportConflictEvent;
use App\Models\ImportRow;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportConflictLifecycleService
{
    public function open(
        ImportRow $row,
        string $type,
        string $severity,
        string $description,
        ?string $subjectType,
        ?int $subjectId,
        array $details,
    ): ImportConflict {
        return DB::transaction(function () use ($row, $type, $severity, $description, $subjectType, $subjectId, $details): ImportConflict {
            $lockedRow = ImportRow::query()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            $identityKey = $this->identityKey($lockedRow, $type, $subjectType, $subjectId, $details);

            $existing = ImportConflict::query()->where('identity_key', $identityKey)->first();
            if ($existing) {
                return $existing;
            }

            // Preserve legacy conflicts that predate identity keys; do not duplicate
            // an already-open matching record while those historical rows remain NULL.
            $legacy = ImportConflict::query()
                ->where('import_row_id', $lockedRow->id)
                ->where('conflict_type', $type)
                ->where('status', 'open')
                ->where('related_row_id', $subjectId)
                ->where('related_subject_type', $subjectType)
                ->where('related_subject_id', $subjectId === null ? null : $subjectId)
                ->first();

            if ($legacy) {
                return $legacy;
            }

            $conflict = ImportConflict::create([
                'import_row_id' => $lockedRow->id,
                'import_batch_id' => $lockedRow->import_batch_id,
                'conflict_type' => $type,
                'identity_key' => $identityKey,
                'severity' => $severity,
                'status' => 'open',
                'description' => $description,
                'related_row_id' => $subjectId,
                'related_subject_type' => $subjectType,
                'related_subject_id' => $subjectId,
                'details' => $details,
            ]);

            ImportConflictEvent::create([
                'import_conflict_id' => $conflict->id,
                'event_type' => 'detected',
                'status_before' => null,
                'status_after' => 'open',
                'actor_type' => 'system',
                'reason' => $description,
                'metadata' => ['conflict_type' => $type, 'details' => $details],
                'idempotency_key' => hash('sha256', 'import-conflict-event:v1|'.$identityKey.'|detected'),
                'occurred_at' => now(),
            ]);

            if ($lockedRow->match_state !== 'conflict') {
                $lockedRow->update(['match_state' => 'conflict']);
            }

            return $conflict;
        });
    }

    public function resolve(ImportRow $row, int $conflictId, int $actorId, string $reason): ImportConflict
    {
        return DB::transaction(function () use ($row, $conflictId, $actorId, $reason): ImportConflict {
            $lockedRow = ImportRow::query()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            $conflict = ImportConflict::query()
                ->whereKey($conflictId)
                ->where('import_row_id', $lockedRow->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($conflict->status === 'resolved') {
                if ($conflict->resolved_by_type === 'user'
                    && (int) $conflict->resolved_by_id === $actorId
                    && $conflict->resolution_reason === $reason) {
                    return $conflict;
                }

                throw new RuntimeException('This conflict has already been resolved; its resolution history is immutable.');
            }

            if ($conflict->status !== 'open') {
                throw new RuntimeException('Only an open conflict can be resolved.');
            }

            $previousMatchState = $lockedRow->match_state;
            $conflict->update([
                'status' => 'resolved',
                'resolved_by_type' => 'user',
                'resolved_by_id' => $actorId,
                'resolution_reason' => $reason,
                'resolved_at' => now(),
            ]);

            $openConflictsRemain = ImportConflict::query()
                ->where('import_row_id', $lockedRow->id)
                ->where('status', 'open')
                ->exists();

            if (! $openConflictsRemain && $lockedRow->match_state === 'conflict') {
                $hasApprovedIdentityDecision = IdentityResolutionDecision::query()
                    ->where('import_row_id', $lockedRow->id)
                    ->whereIn('decision', ['approved_new', 'approved_match'])
                    ->exists();

                $lockedRow->update(['match_state' => $hasApprovedIdentityDecision ? 'matched' : 'new_candidate']);
            }

            $conflict->refresh();
            $identityKey = (string) ($conflict->identity_key ?: hash('sha256', 'legacy-import-conflict:'.$conflict->id));
            $eventKey = hash('sha256', implode('|', [
                'import-conflict-event:v1',
                $identityKey,
                'resolved',
                $actorId,
                hash('sha256', $reason),
            ]));

            ImportConflictEvent::firstOrCreate(
                ['idempotency_key' => $eventKey],
                [
                    'import_conflict_id' => $conflict->id,
                    'event_type' => 'resolved',
                    'status_before' => 'open',
                    'status_after' => 'resolved',
                    'actor_type' => 'user',
                    'actor_id' => $actorId,
                    'reason' => $reason,
                    'metadata' => [
                        'previous_match_state' => $previousMatchState,
                        'new_match_state' => $lockedRow->fresh()->match_state,
                    ],
                    'occurred_at' => now(),
                ]
            );

            return $conflict;
        });
    }

    private function identityKey(
        ImportRow $row,
        string $type,
        ?string $subjectType,
        ?int $subjectId,
        array $details,
    ): string {
        return hash('sha256', json_encode([
            'version' => 1,
            'import_row_id' => (int) $row->id,
            'conflict_type' => $type,
            'related_subject_type' => $subjectType,
            'related_subject_id' => $subjectId,
            'details' => $details,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}

<?php

namespace App\Services;

use App\Models\IdentityMatchCandidate;
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
        array $details
    ): int {
        return DB::transaction(function () use ($row, $type, $severity, $description, $subjectType, $subjectId, $details): int {
            $lockedRow = ImportRow::query()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            $identityKey = hash('sha256', $lockedRow->id.'|'.$type);
            $now = now();

            $inserted = DB::table('import_conflicts')->insertOrIgnore([
                'import_row_id' => $lockedRow->id,
                'import_batch_id' => $lockedRow->import_batch_id,
                'conflict_type' => $type,
                'severity' => $severity,
                'status' => 'open',
                'open_identity_key' => $identityKey,
                'description' => $description,
                'details' => json_encode($details, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'related_row_id' => $subjectId,
                'related_subject_type' => $subjectType,
                'related_subject_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($inserted > 0) {
                $conflict = ImportConflict::query()->where('open_identity_key', $identityKey)->firstOrFail();
                $this->recordEvent($conflict, 'opened', 'system', null, $description, [
                    'conflict_type' => $type,
                    'severity' => $severity,
                    'details' => $details,
                ]);
            } elseif (! ImportConflict::query()->where('open_identity_key', $identityKey)->exists()) {
                throw new RuntimeException('Conflict insertion was ignored without an existing open conflict identity.');
            }

            if ($lockedRow->match_state !== 'conflict') {
                $lockedRow->update(['match_state' => 'conflict']);
            }

            return (int) $inserted;
        });
    }

    public function resolve(ImportConflict $conflict, int $actorId, string $reason): ImportConflict
    {
        return DB::transaction(function () use ($conflict, $actorId, $reason): ImportConflict {
            $lockedConflict = ImportConflict::query()->whereKey($conflict->id)->lockForUpdate()->firstOrFail();

            if ($lockedConflict->status === 'resolved') {
                if ((int) $lockedConflict->resolved_by_id === $actorId
                    && (string) $lockedConflict->resolution_reason === $reason) {
                    return $lockedConflict;
                }

                throw new RuntimeException('This conflict has already been resolved; its original resolution history cannot be overwritten.');
            }

            if ($lockedConflict->status !== 'open') {
                throw new RuntimeException('Only an open conflict can be resolved.');
            }

            $lockedConflict->update([
                'status' => 'resolved',
                'open_identity_key' => null,
                'resolved_by_type' => 'user',
                'resolved_by_id' => $actorId,
                'resolution_reason' => $reason,
                'resolved_at' => now(),
            ]);

            $this->recordEvent($lockedConflict, 'resolved', 'user', $actorId, $reason, [
                'previous_status' => 'open',
                'new_status' => 'resolved',
            ]);

            $row = ImportRow::query()->whereKey($lockedConflict->import_row_id)->lockForUpdate()->firstOrFail();
            $hasOpenConflicts = ImportConflict::query()
                ->where('import_row_id', $row->id)
                ->where('status', 'open')
                ->exists();

            if (! $hasOpenConflicts && $row->match_state === 'conflict') {
                $approvedDecision = IdentityResolutionDecision::query()
                    ->where('import_row_id', $row->id)
                    ->whereIn('decision', ['approved_new', 'approved_match'])
                    ->latest('id')
                    ->first();

                $hasIdentityMatch = IdentityMatchCandidate::query()
                    ->where('import_row_id', $row->id)
                    ->where('status', 'candidate')
                    ->exists();

                $matchState = $approvedDecision?->decision === 'approved_match' || (! $approvedDecision && $hasIdentityMatch)
                    ? 'matched'
                    : 'new_candidate';

                $row->update(['match_state' => $matchState]);
            }

            return $lockedConflict->refresh();
        });
    }

    private function recordEvent(
        ImportConflict $conflict,
        string $eventType,
        string $actorType,
        ?int $actorId,
        ?string $reason,
        array $metadata
    ): void {
        $identity = implode('|', [
            $conflict->id,
            $eventType,
            $actorType,
            $actorId ?? 'null',
            $reason ?? '',
        ]);
        $now = now();

        DB::table('import_conflict_events')->insertOrIgnore([
            'import_conflict_id' => $conflict->id,
            'import_row_id' => $conflict->import_row_id,
            'event_type' => $eventType,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'reason' => $reason,
            'metadata' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'idempotency_key' => hash('sha256', $identity),
            'occurred_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

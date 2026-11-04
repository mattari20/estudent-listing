<?php

namespace App\Services;

use App\Models\IdentityResolutionDecision;
use App\Models\ImportConflict;
use App\Models\ImportPromotionEvent;
use App\Models\ImportRow;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IdentityResolutionDecisionService
{
    public function record(
        ImportRow $row,
        int $actorId,
        string $decision,
        ?int $institutionId,
        string $reason,
    ): IdentityResolutionDecision {
        return DB::transaction(function () use ($row, $actorId, $decision, $institutionId, $reason): IdentityResolutionDecision {
            $lockedRow = ImportRow::query()->with('batch')->whereKey($row->id)->lockForUpdate()->firstOrFail();

            if ($lockedRow->batch?->entity_type !== 'institution') {
                throw new RuntimeException('Identity decisions can only be recorded for institution rows.');
            }

            [$record, $created] = $this->findOrCreateLocked($lockedRow, $actorId, $decision, $institutionId, $reason);

            if (! $created && $lockedRow->status !== 'candidate') {
                $this->recordDecisionAudit($lockedRow, $record, $actorId, $decision, $reason);

                return $record;
            }

            if ($lockedRow->status !== 'candidate') {
                throw new RuntimeException('Identity decisions can only be recorded while the import row is a candidate.');
            }

            $this->recordDecisionAudit($lockedRow, $record, $actorId, $decision, $reason);

            return $record;
        });
    }

    public function rejectRow(ImportRow $row, int $actorId, string $reason, bool $skipOpenConflicts = false): IdentityResolutionDecision
    {
        return DB::transaction(function () use ($row, $actorId, $reason, $skipOpenConflicts): IdentityResolutionDecision {
            $lockedRow = ImportRow::query()->with('batch')->whereKey($row->id)->lockForUpdate()->firstOrFail();

            if ($lockedRow->batch?->entity_type !== 'institution') {
                throw new RuntimeException('Identity rejection is only available for institution rows.');
            }

            if ($skipOpenConflicts && ImportConflict::query()->where('import_row_id', $lockedRow->id)->where('status', 'open')->exists()) {
                throw new RuntimeException('Open import conflicts must be resolved before this bulk rejection.');
            }

            if ($lockedRow->status === 'rejected') {
                $identityKey = $this->identityKey($lockedRow, $actorId, 'rejected', null, $reason);
                $record = IdentityResolutionDecision::query()->where('decision_identity_key', $identityKey)->first();
                if (! $record) {
                    $record = IdentityResolutionDecision::query()
                        ->where('import_row_id', $lockedRow->id)
                        ->whereNull('decision_identity_key')
                        ->where('decision', 'rejected')
                        ->whereNull('institution_id')
                        ->where('actor_type', 'user')
                        ->where('actor_id', $actorId)
                        ->where('reason', $reason)
                        ->first();
                    $record?->update(['decision_identity_key' => $identityKey]);
                }
                if (! $record) {
                    throw new RuntimeException('This import row has already been rejected by a different decision.');
                }
                $this->recordDecisionAudit($lockedRow, $record, $actorId, 'rejected', $reason);
                $this->recordRejectionTransitionAudit($lockedRow, $record, $actorId, $reason, [
                    'new_status' => 'rejected',
                    'operator_action' => 'reject_import_row',
                    'transition_replay' => true,
                ]);

                return $record;
            }

            [$record] = $this->findOrCreateLocked($lockedRow, $actorId, 'rejected', null, $reason);

            if (! in_array($lockedRow->status, ['candidate', 'validated', 'duplicate', 'invalid'], true)) {
                throw new RuntimeException('Only reviewable import rows can be rejected.');
            }

            $previousStatus = $lockedRow->status;
            $previousMatchState = $lockedRow->match_state;
            $note = trim((string) $lockedRow->notes);
            $rejectionNote = 'Rejected by operator: '.$reason;

            $lockedRow->update([
                'status' => 'rejected',
                'notes' => trim($note.($note !== '' ? "\n" : '').$rejectionNote),
            ]);

            $transitionMetadata = [
                'previous_status' => $previousStatus,
                'new_status' => 'rejected',
                'previous_match_state' => $previousMatchState,
                'new_match_state' => $lockedRow->match_state,
                'operator_action' => 'reject_import_row',
            ];
            $this->recordDecisionAudit($lockedRow, $record, $actorId, 'rejected', $reason, $transitionMetadata);
            $this->recordRejectionTransitionAudit($lockedRow, $record, $actorId, $reason, $transitionMetadata);

            return $record;
        });
    }

    private function identityKey(ImportRow $row, int $actorId, string $decision, ?int $institutionId, string $reason): string
    {
        return hash('sha256', json_encode([
            'version' => 1,
            'import_row_id' => (int) $row->id,
            'decision' => $decision,
            'institution_id' => $institutionId,
            'actor_id' => $actorId,
            'reason' => $reason,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /** @return array{IdentityResolutionDecision, bool} */
    private function findOrCreateLocked(
        ImportRow $row,
        int $actorId,
        string $decision,
        ?int $institutionId,
        string $reason,
    ): array {
        $identityKey = $this->identityKey($row, $actorId, $decision, $institutionId, $reason);

        $existing = IdentityResolutionDecision::query()->where('decision_identity_key', $identityKey)->first();
        if ($existing) {
            return [$existing, false];
        }

        $legacy = IdentityResolutionDecision::query()
            ->where('import_row_id', $row->id)
            ->whereNull('decision_identity_key')
            ->where('decision', $decision)
            ->where('institution_id', $institutionId)
            ->where('actor_type', 'user')
            ->where('actor_id', $actorId)
            ->where('reason', $reason)
            ->first();

        if ($legacy) {
            $legacy->update(['decision_identity_key' => $identityKey]);

            return [$legacy, false];
        }

        $record = IdentityResolutionDecision::create([
            'import_row_id' => $row->id,
            'institution_id' => $institutionId,
            'decision' => $decision,
            'actor_type' => 'user',
            'actor_id' => $actorId,
            'reason' => $reason,
            'decided_at' => now(),
            'decision_identity_key' => $identityKey,
        ]);

        return [$record, true];
    }

    private function recordRejectionTransitionAudit(ImportRow $row, IdentityResolutionDecision $record, int $actorId, string $reason, array $metadata): void
    {
        $hasTransitionEvent = ImportPromotionEvent::query()
            ->where('import_row_id', $row->id)
            ->where('decision_type', 'identity_resolution')
            ->where('decision_id', $record->id)
            ->where('event_type', 'promotion_rejected')
            ->get()
            ->contains(fn (ImportPromotionEvent $event): bool => ($event->metadata['operator_action'] ?? null) === 'reject_import_row');

        if (! $hasTransitionEvent) {
            app(ImportPromotionAuditService::class)->record(
                $row,
                'promotion_rejected',
                $actorId,
                $reason,
                $metadata,
                'identity_resolution',
                $record->id,
            );
        }
    }

    private function recordDecisionAudit(
        ImportRow $row,
        IdentityResolutionDecision $record,
        int $actorId,
        string $decision,
        string $reason,
        array $metadata = [],
    ): void {
        $exists = ImportPromotionEvent::query()
            ->where('import_row_id', $row->id)
            ->where('decision_type', 'identity_resolution')
            ->where('decision_id', $record->id)
            ->exists();

        if ($exists) {
            return;
        }

        app(ImportPromotionAuditService::class)->record(
            $row,
            $decision === 'rejected' ? 'promotion_rejected' : 'promotion_approved',
            $actorId,
            $reason,
            array_merge(['decision' => $decision], $metadata),
            'identity_resolution',
            $record->id,
        );
    }
}

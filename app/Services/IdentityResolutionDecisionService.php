<?php

namespace App\Services;

use App\Models\IdentityResolutionDecision;
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

            $identityKey = hash('sha256', json_encode([
                'version' => 1,
                'import_row_id' => (int) $lockedRow->id,
                'decision' => $decision,
                'institution_id' => $institutionId,
                'actor_id' => $actorId,
                'reason' => $reason,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            $legacy = IdentityResolutionDecision::query()
                ->where('import_row_id', $lockedRow->id)
                ->whereNull('decision_identity_key')
                ->where('decision', $decision)
                ->where('institution_id', $institutionId)
                ->where('actor_type', 'user')
                ->where('actor_id', $actorId)
                ->where('reason', $reason)
                ->first();

            if ($legacy) {
                $legacy->update(['decision_identity_key' => $identityKey]);
                $record = $legacy;
            } else {
                $record = IdentityResolutionDecision::firstOrCreate(
                    ['decision_identity_key' => $identityKey],
                [
                    'import_row_id' => $lockedRow->id,
                    'institution_id' => $institutionId,
                    'decision' => $decision,
                    'actor_type' => 'user',
                    'actor_id' => $actorId,
                    'reason' => $reason,
                    'decided_at' => now(),
                    ]
                );
            }

            if ($record->wasRecentlyCreated) {
                app(ImportPromotionAuditService::class)->record(
                    $lockedRow,
                    $decision === 'rejected' ? 'promotion_rejected' : 'promotion_approved',
                    $actorId,
                    $reason,
                    ['decision' => $decision],
                    'identity_resolution',
                    $record->id
                );
            }

            return $record;
        });
    }
}

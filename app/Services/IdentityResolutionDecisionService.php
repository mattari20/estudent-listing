<?php

namespace App\Services;

use App\Models\IdentityResolutionDecision;
use App\Models\ImportRow;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IdentityResolutionDecisionService
{
    /**
     * @return array{decision:IdentityResolutionDecision,created:bool}
     */
    public function record(
        ImportRow $row,
        string $decision,
        ?int $institutionId,
        string $actorType,
        ?int $actorId,
        string $reason,
        ?string $auditEventType = null,
        array $auditMetadata = []
    ): array {
        if (! in_array($decision, ['approved_new', 'approved_match', 'rejected', 'conflict'], true)) {
            throw new RuntimeException('Unsupported identity resolution decision.');
        }
        if ($decision === 'approved_match' && $institutionId === null) {
            throw new RuntimeException('An approved match requires a canonical institution ID.');
        }
        if ($decision === 'approved_new' && $institutionId !== null) {
            throw new RuntimeException('An approved-new decision cannot target an existing institution.');
        }
        if (trim($reason) === '' || trim($actorType) === '') {
            throw new RuntimeException('Identity decision actor and reason are required.');
        }

        return DB::transaction(function () use (
            $row, $decision, $institutionId, $actorType, $actorId, $reason, $auditEventType, $auditMetadata
        ): array {
            $lockedRow = ImportRow::query()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            $identityKey = hash('sha256', implode("\0", [
                $lockedRow->id,
                $decision,
                $institutionId ?? 'null',
                $actorType,
                $actorId ?? 'null',
                $reason,
            ]));
            $now = now();

            $inserted = DB::table('identity_resolution_decisions')->insertOrIgnore([
                'import_row_id' => $lockedRow->id,
                'institution_id' => $institutionId,
                'idempotency_key' => $identityKey,
                'decision' => $decision,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'reason' => $reason,
                'decided_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $recorded = IdentityResolutionDecision::query()
                ->where('idempotency_key', $identityKey)
                ->first();

            if (! $recorded) {
                throw new RuntimeException('Identity decision persistence did not produce a retrievable idempotent record.');
            }

            if ($inserted > 0) {
                $eventType = $auditEventType ?? ($decision === 'rejected' ? 'promotion_rejected' : 'promotion_approved');
                app(ImportPromotionAuditService::class)->record(
                    $lockedRow,
                    $eventType,
                    $actorId,
                    $reason,
                    array_merge($auditMetadata, ['decision' => $decision, 'idempotency_key' => $identityKey]),
                    'identity_resolution',
                    $recorded->id,
                );
            }

            return ['decision' => $recorded, 'created' => $inserted > 0];
        });
    }
}

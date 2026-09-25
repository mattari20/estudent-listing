<?php

namespace App\Services;

use App\Models\IdentityMatchCandidate;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportConflict;
use App\Models\ImportPromotionDecision;
use App\Models\ImportRow;
use Illuminate\Support\Facades\DB;

class BulkImportPromotionService
{
    public function promote(array $rowIds, int $actorId): array
    {
        $result = ['promoted' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($rowIds as $id) {
            try {
                DB::transaction(function () use ($id, $actorId, &$result): void {
                    $row = ImportRow::query()->lockForUpdate()->with('batch')->find($id);

                    if ($row === null || $row->status !== 'candidate') {
                        $result['skipped']++;

                        return;
                    }

                    if (ImportConflict::where('import_row_id', $row->id)->where('status', 'open')->exists()) {
                        $result['skipped']++;

                        return;
                    }

                    $type = $row->batch->entity_type;

                    if ($type === 'institution') {
                        $decision = IdentityResolutionDecision::query()
                            ->where('import_row_id', $row->id)
                            ->whereIn('decision', ['approved_new', 'approved_match'])
                            ->latest('id')
                            ->first();

                        if ($decision === null) {
                            $result['skipped']++;

                            return;
                        }

                        app(ImportPromotionAuditService::class)->record(
                            $row,
                            'promotion_started',
                            $actorId,
                            'Explicitly approved promotion started.',
                            ['previous_status' => $row->status, 'previous_match_state' => $row->match_state],
                            'identity_resolution',
                            $decision->id,
                        );

                        app(InstituteImportPromoter::class)->promote($row, $decision);

                        if ($decision->decision === 'approved_match') {
                            app(ImportChangeDetectionService::class)->detect($row);
                        }
                    } else {
                        $decision = ImportPromotionDecision::query()
                            ->where('import_row_id', $row->id)
                            ->where('decision', 'approved')
                            ->latest('id')
                            ->first();

                        if ($decision === null) {
                            $result['skipped']++;

                            return;
                        }

                        app(ImportPromotionAuditService::class)->record(
                            $row,
                            'promotion_started',
                            $actorId,
                            'Explicitly approved promotion started.',
                            ['previous_status' => $row->status, 'previous_match_state' => $row->match_state],
                            'import_promotion',
                            $decision->id,
                        );

                        app(EnrichmentPromotionService::class)->validateParent($row);

                        $previousStatus = $row->status;
                        $previousMatch = $row->match_state;
                        $entity = app(EnrichmentPromotionService::class)->promote($row);

                        app(ImportPromotionAuditService::class)->record(
                            $row,
                            'promotion_succeeded',
                            $actorId,
                            'Enrichment promoted to canonical data.',
                            [
                                'previous_status' => $previousStatus,
                                'previous_match_state' => $previousMatch,
                                'created_canonical' => false,
                                'canonical_class' => $entity::class,
                            ],
                            'import_promotion',
                            $decision->id,
                        );
                    }

                    $result['promoted']++;
                });
            } catch (\Throwable $e) {
                $result['failed']++;
                $failedRow = ImportRow::with('batch')->find($id);

                if ($failedRow !== null) {
                    app(ImportPromotionAuditService::class)->record(
                        $failedRow,
                        'promotion_failed',
                        $actorId,
                        $e->getMessage(),
                        ['error_class' => $e::class],
                    );
                }
            }
        }

        return $result;
    }

    public function approveAndPromoteNew(int $batchId, int $actorId, string $reason): array
    {
        $result = ['promoted' => 0, 'skipped' => 0, 'failed' => 0, 'matched_or_conflicted' => 0];

        $rowIds = ImportRow::query()
            ->where('import_batch_id', $batchId)
            ->where('status', 'candidate')
            ->orderBy('id')
            ->pluck('id');

        foreach ($rowIds as $id) {
            try {
                DB::transaction(function () use ($id, $actorId, $reason, &$result): void {
                    $row = ImportRow::query()->lockForUpdate()->with('batch')->find($id);

                    if ($row === null || $row->status !== 'candidate') {
                        $result['skipped']++;

                        return;
                    }

                    if (ImportConflict::where('import_row_id', $row->id)->where('status', 'open')->exists()) {
                        $result['matched_or_conflicted']++;

                        return;
                    }

                    if ($row->batch->entity_type !== 'institution') {
                        $result['skipped']++;

                        return;
                    }

                    if (IdentityMatchCandidate::where('import_row_id', $row->id)->exists()) {
                        $result['matched_or_conflicted']++;

                        return;
                    }

                    $existingDecision = IdentityResolutionDecision::query()
                        ->where('import_row_id', $row->id)
                        ->latest('id')
                        ->first();

                    if ($existingDecision !== null && $existingDecision->decision !== 'approved_new') {
                        $result['skipped']++;

                        return;
                    }

                    $decision = $existingDecision ?? IdentityResolutionDecision::create([
                        'import_row_id' => $row->id,
                        'institution_id' => null,
                        'decision' => 'approved_new',
                        'actor_type' => 'user',
                        'actor_id' => $actorId,
                        'reason' => $reason,
                        'decided_at' => now(),
                    ]);

                    if ($existingDecision === null) {
                        app(ImportPromotionAuditService::class)->record(
                            $row,
                            'promotion_approved',
                            $actorId,
                            $reason,
                            ['decision' => 'approved_new', 'bulk' => true],
                            'identity_resolution',
                            $decision->id,
                        );
                    }

                    app(ImportPromotionAuditService::class)->record(
                        $row,
                        'promotion_started',
                        $actorId,
                        'Bulk approved-new promotion started.',
                        ['previous_status' => $row->status, 'previous_match_state' => $row->match_state, 'bulk' => true],
                        'identity_resolution',
                        $decision->id,
                    );

                    app(InstituteImportPromoter::class)->promote($row, $decision);

                    $result['promoted']++;
                });
            } catch (\Throwable $e) {
                $result['failed']++;
                $failedRow = ImportRow::with('batch')->find($id);

                if ($failedRow !== null) {
                    app(ImportPromotionAuditService::class)->record(
                        $failedRow,
                        'promotion_failed',
                        $actorId,
                        $e->getMessage(),
                        ['error_class' => $e::class, 'bulk' => true],
                    );
                }
            }
        }

        return $result;
    }
}

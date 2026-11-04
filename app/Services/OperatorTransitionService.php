<?php

namespace App\Services;

use App\Models\IdentityResolutionDecision;
use App\Models\ImportConflict;
use App\Models\ImportPromotionDecision;
use App\Models\ImportPromotionEvent;
use App\Models\ImportRow;
use App\Models\Institution;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OperatorTransitionService
{
    public function __construct(
        private readonly ImportPromotionAuditService $audit,
        private readonly IdentityResolutionDecisionService $identityDecisions,
    ) {}

    public function rejectRow(ImportRow $row, int $actorId, string $reason, bool $skipOpenConflicts = false): void
    {
        $rowType = $row->batch()->value('entity_type');
        if ($rowType === 'institution') {
            $this->identityDecisions->rejectRow($row, $actorId, $reason, $skipOpenConflicts);

            return;
        }

        DB::transaction(function () use ($row, $actorId, $reason, $skipOpenConflicts): void {
            $locked = ImportRow::query()->with('batch')->whereKey($row->id)->lockForUpdate()->firstOrFail();
            if ($skipOpenConflicts && ImportConflict::query()->where('import_row_id', $locked->id)->where('status', 'open')->exists()) {
                throw new RuntimeException('Open import conflicts must be resolved before this bulk rejection.');
            }

            $existing = ImportPromotionDecision::query()
                ->where('import_row_id', $locked->id)
                ->where('decision', 'rejected')
                ->where('actor_type', 'user')
                ->where('actor_id', $actorId)
                ->where('reason', $reason)
                ->latest('id')
                ->first();

            if ($locked->status === 'rejected') {
                if (! $existing) {
                    throw new RuntimeException('This import row has already been rejected by a different decision.');
                }
                $this->ensurePromotionAudit($locked, $existing, $actorId, $reason, 'reject_import_row');
                $this->ensureRejectionTransitionAudit($locked, $existing, $actorId, $reason, [
                    'new_status' => 'rejected',
                    'operator_action' => 'reject_import_row',
                    'transition_replay' => true,
                ]);

                return;
            }

            if (! in_array($locked->status, ['candidate', 'validated', 'duplicate', 'invalid'], true)) {
                throw new RuntimeException('Only reviewable import rows can be rejected.');
            }

            $previousStatus = $locked->status;
            $previousMatchState = $locked->match_state;
            $decision = $existing ?? ImportPromotionDecision::create([
                'import_row_id' => $locked->id,
                'decision' => 'rejected',
                'actor_type' => 'user',
                'actor_id' => $actorId,
                'reason' => $reason,
                'decided_at' => now(),
            ]);

            $note = trim((string) $locked->notes);
            $locked->update([
                'status' => 'rejected',
                'notes' => trim($note.($note !== '' ? "\n" : '').'Rejected by operator: '.$reason),
            ]);

            $transitionMetadata = [
                'previous_status' => $previousStatus,
                'new_status' => 'rejected',
                'previous_match_state' => $previousMatchState,
                'new_match_state' => $locked->match_state,
                'operator_action' => 'reject_import_row',
            ];
            $this->ensurePromotionAudit($locked, $decision, $actorId, $reason, 'reject_import_row', $transitionMetadata);
            $this->ensureRejectionTransitionAudit($locked, $decision, $actorId, $reason, $transitionMetadata);
        });
    }

    public function recordPromotionDecision(
        ImportRow $row,
        int $actorId,
        string $decision,
        string $reason,
        string $operatorAction,
        ?EnrichmentPromotionService $enrichment = null,
    ): ImportPromotionDecision {
        return DB::transaction(function () use ($row, $actorId, $decision, $reason, $operatorAction, $enrichment): ImportPromotionDecision {
            $locked = ImportRow::query()->with('batch')->whereKey($row->id)->lockForUpdate()->firstOrFail();
            if ($locked->batch?->entity_type === 'institution') {
                throw new RuntimeException('Institution identity rows require an explicit identity-resolution decision.');
            }

            $record = ImportPromotionDecision::query()
                ->where('import_row_id', $locked->id)
                ->where('decision', $decision)
                ->where('actor_type', 'user')
                ->where('actor_id', $actorId)
                ->where('reason', $reason)
                ->latest('id')
                ->first();

            if ($record) {
                $this->ensurePromotionAudit($locked, $record, $actorId, $reason, $operatorAction, [
                    'decision' => $decision,
                    'operator_action' => $operatorAction,
                ]);

                return $record;
            }

            if ($locked->status !== 'candidate') {
                throw new RuntimeException('Promotion decisions can only be recorded while the import row is a candidate.');
            }
            if ($decision === 'approved' && ImportConflict::query()->where('import_row_id', $locked->id)->where('status', 'open')->exists()) {
                throw new RuntimeException('Open import conflicts must be resolved before approval.');
            }
            if ($decision === 'approved' && $enrichment) {
                $enrichment->validateParent($locked);
            }

            $record = ImportPromotionDecision::create([
                'import_row_id' => $locked->id,
                'decision' => $decision,
                'actor_type' => 'user',
                'actor_id' => $actorId,
                'reason' => $reason,
                'decided_at' => now(),
            ]);

            $this->ensurePromotionAudit($locked, $record, $actorId, $reason, $operatorAction, [
                'decision' => $decision,
                'operator_action' => $operatorAction,
            ]);

            return $record;
        });
    }

    public function publishInstitution(ImportRow $row, int $actorId): Institution
    {
        return DB::transaction(function () use ($row, $actorId): Institution {
            $locked = ImportRow::query()->with('batch')->whereKey($row->id)->lockForUpdate()->firstOrFail();
            if ($locked->batch?->entity_type !== 'institution') {
                throw new RuntimeException('Only institution import rows can be published.');
            }
            if ($locked->status !== 'promoted' || $locked->canonical_subject_type !== Institution::class || ! $locked->canonical_subject_id) {
                throw new RuntimeException('Only a promoted institution row can be published.');
            }

            $institution = Institution::query()->whereKey($locked->canonical_subject_id)->lockForUpdate()->firstOrFail();
            if ($institution->status === 'active') {
                return $institution;
            }

            $decision = IdentityResolutionDecision::query()
                ->where('import_row_id', $locked->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();
            if (! $decision || ! in_array($decision->decision, ['approved_new', 'approved_match'], true)) {
                throw new RuntimeException('The latest identity decision must be explicitly approved before publication.');
            }

            $hasActiveEvidence = \App\Models\Assertion::query()
                ->where('subject_type', ImportRow::class)
                ->where('subject_id', $locked->id)
                ->whereHas('evidence', fn ($query) => $query->where('status', 'active'))
                ->exists();
            if (! $hasActiveEvidence) {
                throw new RuntimeException('Active official source evidence is required before publication.');
            }
            if (ImportConflict::query()->where('import_row_id', $locked->id)->where('status', 'open')->exists()) {
                throw new RuntimeException('Resolve all open import conflicts before publication.');
            }

            $previousStatus = $institution->status;
            $institution->update(['status' => 'active', 'verified_at' => now()]);

            $this->audit->record(
                $locked,
                'institution_published',
                $actorId,
                'Operator explicitly published the promoted institution to the public directory.',
                ['institution_id' => $institution->id, 'previous_status' => $previousStatus, 'new_status' => 'active'],
                'identity_resolution',
                $decision->id,
            );

            return $institution;
        });
    }

    public function promoteEnrichment(ImportRow $row, int $actorId, EnrichmentPromotionService $service): object
    {
        return DB::transaction(function () use ($row, $actorId, $service): object {
            $locked = ImportRow::query()->with('batch')->whereKey($row->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'candidate') {
                throw new RuntimeException('Only candidate import rows can be promoted.');
            }
            if (ImportConflict::query()->where('import_row_id', $locked->id)->where('status', 'open')->exists()) {
                throw new RuntimeException('Open import conflicts must be resolved before promotion.');
            }
            $decision = ImportPromotionDecision::query()->where('import_row_id', $locked->id)->latest('id')->lockForUpdate()->first();
            if (! $decision || $decision->decision !== 'approved') {
                throw new RuntimeException('The latest enrichment decision must be approved before promotion.');
            }
            $service->validateParent($locked);
            $previousStatus = $locked->status;
            $previousMatchState = $locked->match_state;
            $entity = $service->promote($locked);
            $this->audit->record($locked, 'promotion_succeeded', $actorId, 'Enrichment promoted to canonical data.', [
                'previous_status' => $previousStatus,
                'previous_match_state' => $previousMatchState,
                'created_canonical' => false,
                'canonical_class' => $entity::class,
            ], 'import_promotion', $decision->id);

            return $entity;
        });
    }

    public function promoteGeography(ImportRow $row, int $actorId, GeographyPromotionService $service): object
    {
        return DB::transaction(function () use ($row, $actorId, $service): object {
            $locked = ImportRow::query()->with('batch')->whereKey($row->id)->lockForUpdate()->firstOrFail();
            if ($locked->batch?->entity_type !== 'geography' || $locked->status !== 'candidate') {
                throw new RuntimeException('Only candidate geography rows can be promoted.');
            }
            if (ImportConflict::query()->where('import_row_id', $locked->id)->where('status', 'open')->exists()) {
                throw new RuntimeException('Open import conflicts must be resolved before promotion.');
            }
            $decision = ImportPromotionDecision::query()->where('import_row_id', $locked->id)->latest('id')->lockForUpdate()->first();
            if (! $decision || $decision->decision !== 'approved') {
                throw new RuntimeException('The latest geography decision must be approved before promotion.');
            }
            $previousStatus = $locked->status;
            $previousMatchState = $locked->match_state;
            $entity = $service->promote($locked);
            $this->audit->record($locked, 'promotion_succeeded', $actorId, 'Geography promoted to canonical data.', [
                'previous_status' => $previousStatus,
                'previous_match_state' => $previousMatchState,
                'canonical_id' => $entity->id,
                'canonical_class' => $entity::class,
            ], 'geography_promotion', $decision->id);

            return $entity;
        });
    }

    private function ensureRejectionTransitionAudit(ImportRow $row, ImportPromotionDecision $decision, int $actorId, string $reason, array $metadata): void
    {
        $hasTransitionEvent = ImportPromotionEvent::query()
            ->where('import_row_id', $row->id)
            ->where('decision_type', 'import_promotion')
            ->where('decision_id', $decision->id)
            ->where('event_type', 'promotion_rejected')
            ->get()
            ->contains(fn (ImportPromotionEvent $event): bool => ($event->metadata['operator_action'] ?? null) === 'reject_import_row');

        if (! $hasTransitionEvent) {
            $this->audit->record($row, 'promotion_rejected', $actorId, $reason, $metadata, 'import_promotion', $decision->id);
        }
    }

    private function ensurePromotionAudit(
        ImportRow $row,
        ImportPromotionDecision $decision,
        int $actorId,
        string $reason,
        string $operatorAction,
        array $metadata = [],
    ): void {
        $exists = ImportPromotionEvent::query()
            ->where('import_row_id', $row->id)
            ->where('decision_type', 'import_promotion')
            ->where('decision_id', $decision->id)
            ->exists();
        if ($exists) {
            return;
        }
        $this->audit->record(
            $row,
            $decision->decision === 'approved' ? 'promotion_approved' : 'promotion_rejected',
            $actorId,
            $reason,
            array_merge(['operator_action' => $operatorAction], $metadata),
            'import_promotion',
            $decision->id,
        );
    }
}

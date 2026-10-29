<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\Institution;
use App\Models\InstitutionFieldApproval;
use App\Models\OfficialSourceRefreshEvent;
use App\Models\Source;
use App\Models\SourceChangeObservation;
use App\Models\SourceSnapshot;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class InstitutionFieldApprovalService
{
    public function createPending(
        int $institutionId,
        int $sourceId,
        int $observationId,
        int $snapshotId,
        int $evidenceId,
        ?string $expectedCurrentValue,
        string $proposedValue
    ): InstitutionFieldApproval {
        $actor = Auth::user();
        if (! $actor instanceof User || ! $actor->isOperator()) {
            throw new RuntimeException('An authenticated institute operator is required to create a proposal.');
        }
        if (trim($proposedValue) === '') {
            throw new RuntimeException('The proposed Institution description must not be empty.');
        }

        return DB::transaction(function () use ($actor, $institutionId, $sourceId, $observationId, $snapshotId, $evidenceId, $expectedCurrentValue, $proposedValue): InstitutionFieldApproval {
            $institution = Institution::query()->lockForUpdate()->findOrFail($institutionId);
            if ($this->normalize($institution->description) !== $this->normalize($expectedCurrentValue)) {
                throw new RuntimeException('Expected Institution description is stale; create a fresh proposal.');
            }

            $source = Source::query()->findOrFail($sourceId);
            $observation = SourceChangeObservation::query()->findOrFail($observationId);
            $snapshot = SourceSnapshot::query()->findOrFail($snapshotId);
            $evidence = Evidence::query()->findOrFail($evidenceId);

            $values = $this->validatedEvidenceValues($source, $observation, $snapshot, $evidence);
            $attributes = [
                'institution_id' => $institution->id,
                'field_name' => 'description',
                'expected_current_value' => $expectedCurrentValue,
                'proposed_value' => $proposedValue,
                'source_id' => $source->id,
                'source_change_observation_id' => $observation->id,
                'source_snapshot_id' => $snapshot->id,
                'evidence_id' => $evidence->id,
                'evidence_url' => $values['evidence_url'],
                'evidence_excerpt' => $values['evidence_excerpt'],
                'content_fingerprint' => $values['fingerprint'],
                'source_observed_at' => $values['source_observed_at'],
                'evidence_observed_at' => $values['evidence_observed_at'],
            ];
            $hash = $this->proposalHash($attributes);
            if (InstitutionFieldApproval::query()->where('proposal_hash', $hash)->exists()) {
                throw new RuntimeException('This exact proposal already exists; terminal proposals cannot be reused.');
            }

            return InstitutionFieldApproval::query()->create($attributes + [
                'proposal_hash' => $hash,
                'status' => 'pending',
                'created_by' => $actor->id,
            ]);
        }, 3);
    }

    public function decide(int $proposalId, string $decision, ?string $reason = null): InstitutionFieldApproval
    {
        $reviewer = Auth::user();
        if (! $reviewer instanceof User || ! $reviewer->isAdmin()) {
            throw new RuntimeException('An authenticated institute admin is required to review a proposal.');
        }
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw new RuntimeException('Decision must be approved or rejected.');
        }
        if ($decision === 'rejected' && trim((string) $reason) === '') {
            throw new RuntimeException('A rejection reason is required.');
        }

        return DB::transaction(function () use ($reviewer, $proposalId, $decision, $reason): InstitutionFieldApproval {
            $proposal = InstitutionFieldApproval::query()->lockForUpdate()->findOrFail($proposalId);
            if ($proposal->status !== 'pending' || $proposal->decision_at !== null) {
                throw new RuntimeException('Only a pending, undecided proposal can be reviewed.');
            }
            if ((int) $proposal->created_by === (int) $reviewer->id) {
                throw new RuntimeException('The proposal creator cannot review their own proposal.');
            }

            if ($decision === 'approved') {
                $this->assertProposalIntegrity($proposal);
                $this->validatedEvidenceValues(
                    $proposal->source()->firstOrFail(),
                    $proposal->observation()->firstOrFail(),
                    $proposal->snapshot()->firstOrFail(),
                    $proposal->evidence()->firstOrFail(),
                    $proposal
                );
                if ($this->normalize($proposal->institution()->firstOrFail()->description) !== $this->normalize($proposal->expected_current_value)) {
                    throw new RuntimeException('The canonical description changed; this proposal must be recreated.');
                }
            }

            $proposal->status = $decision;
            $proposal->reviewer_id = $reviewer->id;
            $proposal->decision_at = now();
            $proposal->decision_reason = $reason;
            $proposal->save();

            return $proposal->refresh();
        }, 3);
    }

    /**
     * Apply one approved, evidence-bound Institution description proposal.
     *
     * @return array{outcome:string,institution:?Institution,approval:InstitutionFieldApproval}
     */
    public function applyApprovedInstitutionDescription(int $proposalId): array
    {
        return DB::transaction(function () use ($proposalId): array {
            $proposal = InstitutionFieldApproval::query()->lockForUpdate()->findOrFail($proposalId);
            if ($proposal->status !== 'approved' || $proposal->decision_at === null || $proposal->reviewer_id === null || $proposal->applied_at !== null) {
                throw new RuntimeException('Only an approved, unapplied proposal can be applied.');
            }
            if ($proposal->field_name !== 'description') {
                throw new RuntimeException('Only Institution description proposals may be applied.');
            }

            $institution = Institution::query()->lockForUpdate()->findOrFail($proposal->institution_id);
            try {
                $this->assertProposalIntegrity($proposal);
                $source = $proposal->source()->firstOrFail();
                $observation = $proposal->observation()->firstOrFail();
                $snapshot = $proposal->snapshot()->firstOrFail();
                $evidence = $proposal->evidence()->firstOrFail();
                $values = $this->validatedEvidenceValues($source, $observation, $snapshot, $evidence, $proposal);
            } catch (Throwable $exception) {
                $proposal->status = 'stale';
                $proposal->application_outcome = 'evidence_invalid';
                $proposal->applied_at = now();
                $proposal->save();

                return ['outcome' => 'stale', 'institution' => null, 'approval' => $proposal->refresh()];
            }

            if ($this->normalize($institution->description) !== $this->normalize($proposal->expected_current_value)) {
                $proposal->status = 'stale';
                $proposal->application_outcome = 'expected_value_changed';
                $proposal->applied_at = now();
                $proposal->save();

                return ['outcome' => 'stale', 'institution' => $institution, 'approval' => $proposal->refresh()];
            }

            $policy = app(OfficialSourceRefreshPolicyService::class)->evaluate([
                'source_official' => true,
                'source_identity_verified' => true,
                'fetch_successful' => true,
                'entity_match_confirmed' => true,
                'evidence_url' => $values['evidence_url'],
                'evidence_excerpt' => $values['evidence_excerpt'],
                'observed_at' => $values['source_observed_at'],
                'content_fingerprint' => $values['fingerprint'],
                'previous_value' => $proposal->expected_current_value,
                'proposed_value' => $proposal->proposed_value,
                'allow_no_change' => true,
                'entity_identity_ambiguous' => false,
                'source_conflict' => false,
            ]);
            if (! $policy['eligible']) {
                $proposal->status = 'stale';
                $proposal->application_outcome = 'evidence_blocked';
                $proposal->applied_at = now();
                $proposal->save();

                return ['outcome' => 'stale', 'institution' => $institution, 'approval' => $proposal->refresh()];
            }

            if ($this->normalize($institution->description) === $this->normalize($proposal->proposed_value)) {
                $proposal->status = 'applied';
                $proposal->application_outcome = 'no_change';
                $proposal->applied_at = now();
                $proposal->save();

                return ['outcome' => 'no_change', 'institution' => $institution, 'approval' => $proposal->refresh()];
            }

            $before = $institution->description;
            $institution->description = $proposal->proposed_value;
            $institution->save();

            $event = OfficialSourceRefreshEvent::query()->create([
                'source_id' => $source->id,
                'source_change_observation_id' => $observation->id,
                'canonical_type' => Institution::class,
                'canonical_id' => $institution->id,
                'field_name' => 'description',
                'before_value' => ['value' => $before],
                'after_value' => ['value' => $proposal->proposed_value],
                'evidence_url' => $values['evidence_url'],
                'evidence_excerpt' => $values['evidence_excerpt'],
                'content_fingerprint' => $values['fingerprint'],
                'observed_at' => $values['source_observed_at'],
                'occurred_at' => now(),
            ]);

            $proposal->status = 'applied';
            $proposal->application_outcome = 'applied';
            $proposal->applied_refresh_event_id = $event->id;
            $proposal->applied_at = now();
            $proposal->save();

            return ['outcome' => 'applied', 'institution' => $institution->refresh(), 'approval' => $proposal->refresh()];
        }, 3);
    }

    private function assertProposalIntegrity(InstitutionFieldApproval $proposal): void
    {
        $attributes = [
            'institution_id' => $proposal->institution_id,
            'field_name' => $proposal->field_name,
            'expected_current_value' => $proposal->expected_current_value,
            'proposed_value' => $proposal->proposed_value,
            'source_id' => $proposal->source_id,
            'source_change_observation_id' => $proposal->source_change_observation_id,
            'source_snapshot_id' => $proposal->source_snapshot_id,
            'evidence_id' => $proposal->evidence_id,
            'evidence_url' => $proposal->evidence_url,
            'evidence_excerpt' => $proposal->evidence_excerpt,
            'content_fingerprint' => $proposal->content_fingerprint,
            'source_observed_at' => $proposal->source_observed_at,
            'evidence_observed_at' => $proposal->evidence_observed_at,
        ];
        if (! hash_equals((string) $proposal->proposal_hash, $this->proposalHash($attributes))) {
            throw new RuntimeException('The persisted proposal binding has changed.');
        }
    }

    /**
     * @return array{evidence_url:string,evidence_excerpt:string,fingerprint:string,source_observed_at:string,evidence_observed_at:?string}
     */
    private function validatedEvidenceValues(
        Source $source,
        SourceChangeObservation $observation,
        SourceSnapshot $snapshot,
        Evidence $evidence,
        ?InstitutionFieldApproval $proposal = null
    ): array {
        $identity = app(OfficialSourceIdentityVerifier::class)->verify($source);
        if (! $identity['verified']) {
            throw new RuntimeException('Official source identity is not verified: '.implode(',', $identity['blockers']));
        }
        if ($observation->source_id !== $source->id
            || $observation->current_snapshot_id !== $snapshot->id
            || $snapshot->source_id !== $source->id
            || $snapshot->status !== 'captured'
            || $evidence->source_id !== $source->id
            || $evidence->snapshot_id !== $snapshot->id
            || $evidence->status !== 'active') {
            throw new RuntimeException('Source observation, snapshot, and evidence relationships are invalid.');
        }
        if (! is_string($snapshot->content_hash) || trim($snapshot->content_hash) === ''
            || ! is_string($evidence->content_hash) || ! hash_equals($snapshot->content_hash, $evidence->content_hash)) {
            throw new RuntimeException('Evidence fingerprint does not match the captured source snapshot.');
        }
        if (! is_string($evidence->reference) || ! filter_var($evidence->reference, FILTER_VALIDATE_URL)
            || ! in_array(strtolower((string) parse_url($evidence->reference, PHP_URL_SCHEME)), ['http', 'https'], true)
            || ! is_string($evidence->excerpt) || trim($evidence->excerpt) === ''
            || $observation->observed_at === null) {
            throw new RuntimeException('A valid evidence URL, excerpt, and source observation timestamp are required.');
        }

        $sourceObservedAt = $this->timestamp($observation->observed_at);
        $evidenceObservedAt = $evidence->observed_at === null ? null : $this->timestamp($evidence->observed_at);
        $values = [
            'evidence_url' => $evidence->reference,
            'evidence_excerpt' => $evidence->excerpt,
            'fingerprint' => $snapshot->content_hash,
            'source_observed_at' => $sourceObservedAt,
            'evidence_observed_at' => $evidenceObservedAt,
        ];

        if ($proposal !== null && (
            $proposal->source_id !== $source->id
            || $proposal->source_change_observation_id !== $observation->id
            || $proposal->source_snapshot_id !== $snapshot->id
            || $proposal->evidence_id !== $evidence->id
            || $proposal->evidence_url !== $values['evidence_url']
            || $proposal->evidence_excerpt !== $values['evidence_excerpt']
            || $proposal->content_fingerprint !== $values['fingerprint']
            || $this->timestamp($proposal->source_observed_at) !== $values['source_observed_at']
            || $this->nullableTimestamp($proposal->evidence_observed_at) !== $values['evidence_observed_at']
        )) {
            throw new RuntimeException('Evidence or source revision changed after review.');
        }

        return $values;
    }

    private function proposalHash(array $attributes): string
    {
        foreach (['source_observed_at', 'evidence_observed_at'] as $field) {
            $attributes[$field] = $attributes[$field] === null ? null : $this->timestamp($attributes[$field]);
        }
        ksort($attributes);

        return hash('sha256', json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function timestamp(mixed $value): string
    {
        return \Illuminate\Support\Carbon::parse($value)->utc()->format('Y-m-d\TH:i:s\Z');
    }

    private function nullableTimestamp(mixed $value): ?string
    {
        return $value === null ? null : $this->timestamp($value);
    }

    private function normalize(mixed $value): string
    {
        return $value === null ? '__NULL__' : trim((string) $value);
    }
}

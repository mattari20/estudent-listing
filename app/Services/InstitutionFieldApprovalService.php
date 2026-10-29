<?php

namespace App\Services;

use App\Models\Assertion;
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
            if ($institution->description !== $expectedCurrentValue) {
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
                $this->assertNoConflictingVerifiedAssertions($proposal);
                if ($proposal->institution()->firstOrFail()->description !== $proposal->expected_current_value) {
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
        return app(OfficialSourceCanonicalRefreshService::class)->refreshApprovedInstitutionDescription($proposalId);
    }

    public function assertProposalIntegrity(InstitutionFieldApproval $proposal): void
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

    public function assertNoConflictingVerifiedAssertions(InstitutionFieldApproval $proposal): void
    {
        $conflicts = Assertion::query()
            ->where('subject_id', $proposal->institution_id)
            ->whereIn('subject_type', [Institution::class, 'institution'])
            ->where('attribute', 'description')
            ->whereIn('verification_state', ['verified', 'confirmed'])
            ->where('value', '!=', $proposal->proposed_value)
            ->whereHas('evidence', function ($query) use ($proposal): void {
                $query->where('status', 'active')->where('id', '!=', $proposal->evidence_id);
            })
            ->exists();

        if ($conflicts) {
            throw new RuntimeException('Conflicting active verified evidence exists for the Institution description.');
        }
    }

    /**
     * Revalidate all persisted source/evidence relationships for a saved proposal.
     *
     * @return array{evidence_url:string,evidence_excerpt:string,fingerprint:string,source_observed_at:string,evidence_observed_at:?string}
     */
    public function validatedEvidenceForProposal(InstitutionFieldApproval $proposal): array
    {
        return $this->validatedEvidenceValues(
            $proposal->source()->firstOrFail(),
            $proposal->observation()->firstOrFail(),
            $proposal->snapshot()->firstOrFail(),
            $proposal->evidence()->firstOrFail(),
            $proposal
        );
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
            || ! is_string($snapshot->content) || ! hash_equals($snapshot->content_hash, hash('sha256', $snapshot->content))
            || ! is_string($evidence->content_hash) || ! hash_equals($snapshot->content_hash, $evidence->content_hash)) {
            throw new RuntimeException('Captured snapshot content fingerprint is invalid or does not match the evidence.');
        }
        if (! is_string($evidence->reference) || ! filter_var($evidence->reference, FILTER_VALIDATE_URL)
            || ! in_array(strtolower((string) parse_url($evidence->reference, PHP_URL_SCHEME)), ['http', 'https'], true)
            || ! is_string($evidence->excerpt) || trim($evidence->excerpt) === ''
            || $observation->observed_at === null) {
            throw new RuntimeException('A valid evidence URL, excerpt, and source observation timestamp are required.');
        }
        try {
            app(SourceUrlGuard::class)->validate($evidence->reference);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Evidence URL is not a safe public HTTP destination.', 0, $exception);
        }
        $sourceHost = strtolower((string) parse_url((string) $source->url, PHP_URL_HOST));
        $evidenceHost = strtolower((string) parse_url($evidence->reference, PHP_URL_HOST));
        if ($sourceHost === '' || $evidenceHost !== $sourceHost) {
            throw new RuntimeException('Evidence URL host does not match the verified official source.');
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
            (int) $proposal->source_id !== (int) $source->id
            || (int) $proposal->source_change_observation_id !== (int) $observation->id
            || (int) $proposal->source_snapshot_id !== (int) $snapshot->id
            || (int) $proposal->evidence_id !== (int) $evidence->id
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
        foreach (['institution_id', 'source_id', 'source_change_observation_id', 'source_snapshot_id', 'evidence_id'] as $field) {
            $attributes[$field] = (int) $attributes[$field];
        }
        // The persisted timestamp fields are checked directly against the live
        // observation and evidence records during review and application. Exclude
        // their driver-dependent date serialization from the immutable payload hash.
        unset($attributes['source_observed_at'], $attributes['evidence_observed_at']);
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

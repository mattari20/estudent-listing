<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\Institution;
use App\Models\InstitutionFieldApproval;
use App\Models\OfficialSourceRefreshEvent;
use App\Models\Program;
use App\Models\ProgramOffering;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OfficialSourceCanonicalRefreshService
{
    /**
     * Institution refresh is deliberately limited to descriptive prose. Identity,
     * classification, status, verification state, keys, and relationships are not
     * safe to revise until a persisted field-level approval decision is integrated.
     */
    private const ALLOWED_FIELDS = [
        Institution::class => ['description'],
        Campus::class => ['name', 'campus_type', 'status', 'address', 'postal_code', 'latitude', 'longitude'],
        Program::class => ['name', 'program_level', 'award', 'discipline', 'status', 'description'],
        ProgramOffering::class => ['delivery_mode', 'status', 'intake_label', 'duration', 'availability', 'effective_from', 'effective_until'],
    ];

    public function __construct(
        private readonly ?OfficialSourceRefreshPolicyService $policy = null
    ) {
    }

    /**
     * Apply one evidence-backed field correction. Caller must supply an expected
     * current value captured during review; stale proposals fail closed.
     *
     * @param array<string, mixed> $evidence
     */
    public function refresh(
        Model $canonical,
        string $field,
        mixed $expectedCurrentValue,
        mixed $proposedValue,
        array $evidence
    ): Model {
        $class = $canonical::class;
        if (! isset(self::ALLOWED_FIELDS[$class]) || ! in_array($field, self::ALLOWED_FIELDS[$class], true)) {
            throw new RuntimeException('Canonical field is not allowlisted for official refresh.');
        }

        // Institution writes must use the persisted proposal ID path below. The generic
        // evidence-array path is intentionally never an authorization mechanism.
        if ($class === Institution::class) {
            throw new RuntimeException(
                'Institution official refresh requires a persisted approved proposal ID.'
            );
        }

        return DB::transaction(function () use ($canonical, $class, $field, $expectedCurrentValue, $proposedValue, $evidence): Model {
            /** @var Model $locked */
            $locked = $class::query()->lockForUpdate()->findOrFail($canonical->getKey());
            $currentValue = $locked->getAttribute($field);

            if ($this->normalize($currentValue) !== $this->normalize($expectedCurrentValue)) {
                throw new RuntimeException('Canonical field changed after review; refresh requires re-review.');
            }

            $proposal = [
                'source_official' => ($evidence['source_official'] ?? false) === true,
                'source_identity_verified' => ($evidence['source_identity_verified'] ?? false) === true,
                'fetch_successful' => ($evidence['fetch_successful'] ?? false) === true,
                'entity_match_confirmed' => ($evidence['entity_match_confirmed'] ?? false) === true,
                'evidence_url' => $evidence['evidence_url'] ?? null,
                'evidence_excerpt' => $evidence['evidence_excerpt'] ?? null,
                'observed_at' => $evidence['observed_at'] ?? null,
                'content_fingerprint' => $evidence['content_fingerprint'] ?? null,
                'previous_value' => $currentValue,
                'proposed_value' => $proposedValue,
                'entity_identity_ambiguous' => ($evidence['entity_identity_ambiguous'] ?? false) === true,
                'source_conflict' => ($evidence['source_conflict'] ?? false) === true,
            ];
            $decision = ($this->policy ?? app(OfficialSourceRefreshPolicyService::class))->evaluate($proposal);
            if (! $decision['eligible']) {
                throw new RuntimeException('Official source refresh blocked: '.implode(',', $decision['blockers']));
            }

            $locked->setAttribute($field, $proposedValue);
            $locked->save();

            OfficialSourceRefreshEvent::query()->create([
                'source_id' => $evidence['source_id'] ?? null,
                'source_change_observation_id' => $evidence['source_change_observation_id'] ?? null,
                'canonical_type' => $class,
                'canonical_id' => $locked->getKey(),
                'field_name' => $field,
                'before_value' => ['value' => $currentValue],
                'after_value' => ['value' => $proposedValue],
                'evidence_url' => $evidence['evidence_url'],
                'evidence_excerpt' => $evidence['evidence_excerpt'],
                'content_fingerprint' => $evidence['content_fingerprint'],
                'observed_at' => $evidence['observed_at'],
                'occurred_at' => now(),
            ]);

            return $locked;
        }, 3);
    }

    /**
     * Apply one approved, evidence-bound Institution description proposal.
     *
     * @return array{outcome:string,institution:?Institution,approval:InstitutionFieldApproval}
     */
    public function refreshApprovedInstitutionDescription(int $proposalId): array
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
            $approvals = app(InstitutionFieldApprovalService::class);
            try {
                $approvals->assertProposalIntegrity($proposal);
                $values = $approvals->validatedEvidenceForProposal($proposal);
                $approvals->assertNoConflictingVerifiedAssertions($proposal);
            } catch (RuntimeException $exception) {
                $proposal->status = 'stale';
                $proposal->application_outcome = 'evidence_invalid';
                $proposal->applied_at = now();
                $proposal->save();

                return ['outcome' => 'stale', 'institution' => null, 'approval' => $proposal->refresh()];
            }

            if ($institution->description !== $proposal->expected_current_value) {
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

            if ($institution->description === $proposal->proposed_value) {
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
                'source_id' => $proposal->source_id,
                'source_change_observation_id' => $proposal->source_change_observation_id,
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

    private function normalize(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value === null) {
            return '__NULL__';
        }

        return trim((string) $value);
    }
}

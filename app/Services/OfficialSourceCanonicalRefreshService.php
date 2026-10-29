<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\Institution;
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

        // SourceChangeObservation currently records detection/actionability, not a
        // persisted human approval of this exact field proposal. Import promotion
        // decisions are tied to import-row promotion and cannot authorize a field
        // overwrite. Do not trust caller-supplied booleans as an approval substitute.
        if ($class === Institution::class) {
            throw new RuntimeException(
                'Institution official refresh is blocked: persisted field-level approval integration is unavailable.'
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

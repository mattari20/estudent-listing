<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Deterministic quality gates for discovery output.
 *
 * These checks are intentionally conservative and never treat model confidence
 * as proof of official evidence or permission to mutate canonical records.
 */
class InstituteDiscoveryCandidateQualityService
{
    /**
     * @return array<string>
     */
    public function blockers(
        string $entityType,
        array $candidate,
        string $sourceUrl,
        bool $useAi
    ): array {
        $blockers = [];
        $name = trim((string) ($candidate['name'] ?? ''));
        $slug = Str::slug($name);
        $confidence = $candidate['confidence'] ?? null;

        if ($name === '' || mb_strlen($name) > 180) {
            $blockers[] = 'candidate_name_missing_or_invalid';
        }

        if ($slug === '') {
            $blockers[] = 'candidate_slug_empty';
        }

        $sourceParts = parse_url($sourceUrl);
        if (! filter_var($sourceUrl, FILTER_VALIDATE_URL)
            || ! is_array($sourceParts)
            || ! in_array(strtolower((string) ($sourceParts['scheme'] ?? '')), ['http', 'https'], true)
            || empty($sourceParts['host'])
            || isset($sourceParts['user'])
            || isset($sourceParts['pass'])) {
            $blockers[] = 'source_url_invalid';
        }

        if ($confidence !== null
            && (! is_numeric($confidence) || (float) $confidence < 0 || (float) $confidence > 1)) {
            $blockers[] = 'confidence_out_of_range';
        }

        if ($useAi && trim((string) ($candidate['evidence_excerpt'] ?? '')) === '') {
            $blockers[] = 'ai_candidate_missing_evidence_excerpt';
        }

        if ($entityType === 'institution') {
            foreach (['official_name', 'short_name', 'institution_type', 'evidence_excerpt'] as $field) {
                if (! is_string($candidate[$field] ?? null) || trim($candidate[$field]) === '') {
                    $blockers[] = 'institution_identity_missing_'.$field;
                }
            }

            if (preg_match('/(^|[^a-z])campus([^a-z]|$)/i', $name) === 1) {
                $blockers[] = 'institution_candidate_looks_like_campus';
            }
        }

        if (! in_array($entityType, ['program', 'campus', 'institution'], true)) {
            $blockers[] = 'unsupported_entity_type';
        }

        return array_values(array_unique($blockers));
    }
}

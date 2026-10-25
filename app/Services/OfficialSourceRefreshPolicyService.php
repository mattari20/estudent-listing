<?php

namespace App\Services;

use DateTimeImmutable;

/**
 * Deterministic gate for applying field-level corrections from official sources.
 *
 * This service classifies proposals only. Canonical writes must still occur in
 * the governed, transactional promotion path with current-version revalidation.
 */
class OfficialSourceRefreshPolicyService
{
    /**
     * @param array<string, mixed> $proposal
     * @return array{eligible:bool,state:string,blockers:array<string>,reason:string}
     */
    public function evaluate(array $proposal): array
    {
        $blockers = [];

        if (($proposal['source_official'] ?? false) !== true) {
            $blockers[] = 'official_source_not_verified';
        }
        if (($proposal['source_identity_verified'] ?? false) !== true) {
            $blockers[] = 'source_identity_not_verified';
        }
        if (($proposal['fetch_successful'] ?? false) !== true) {
            $blockers[] = 'source_fetch_not_successful';
        }
        if (($proposal['entity_match_confirmed'] ?? false) !== true) {
            $blockers[] = 'canonical_entity_match_not_confirmed';
        }
        if (! is_string($proposal['evidence_url'] ?? null)
            || ! filter_var($proposal['evidence_url'], FILTER_VALIDATE_URL)
            || ! in_array(strtolower((string) parse_url($proposal['evidence_url'], PHP_URL_SCHEME)), ['http', 'https'], true)
            || parse_url($proposal['evidence_url'], PHP_URL_HOST) === false
            || parse_url($proposal['evidence_url'], PHP_URL_HOST) === null
            || parse_url($proposal['evidence_url'], PHP_URL_USER) !== null
            || parse_url($proposal['evidence_url'], PHP_URL_PASS) !== null) {
            $blockers[] = 'valid_field_evidence_url_required';
        }
        if (! is_string($proposal['evidence_excerpt'] ?? null) || trim($proposal['evidence_excerpt']) === '') {
            $blockers[] = 'field_evidence_excerpt_required';
        }
        if (! $this->isValidTimestamp($proposal['observed_at'] ?? null)) {
            $blockers[] = 'source_observation_timestamp_required';
        }
        if (! is_string($proposal['content_fingerprint'] ?? null) || trim($proposal['content_fingerprint']) === '') {
            $blockers[] = 'source_content_fingerprint_required';
        }
        if (! array_key_exists('previous_value', $proposal) || ! array_key_exists('proposed_value', $proposal)) {
            $blockers[] = 'before_after_values_required';
        } elseif ($this->normalize($proposal['previous_value']) === $this->normalize($proposal['proposed_value'])) {
            $blockers[] = 'no_material_field_change';
        }
        if (($proposal['entity_identity_ambiguous'] ?? false) === true) {
            $blockers[] = 'ambiguous_entity_identity';
        }
        if (($proposal['source_conflict'] ?? false) === true) {
            $blockers[] = 'conflicting_official_evidence';
        }

        $blockers = array_values(array_unique($blockers));
        $eligible = $blockers === [];

        return [
            'eligible' => $eligible,
            'state' => $eligible ? 'refresh_eligible' : 'blocked',
            'blockers' => $blockers,
            'reason' => $eligible
                ? 'Verified official-source evidence supports a field correction; revalidate the latest source and canonical version transactionally before applying.'
                : 'Official-source refresh is blocked until every source, identity, and field-evidence requirement passes.',
        ];
    }

    private function isValidTimestamp(mixed $value): bool
    {
        if (! is_string($value) || trim($value) === '') {
            return false;
        }

        try {
            // Require an explicit timezone so timestamps from different sources
            // cannot be silently interpreted in the server's local timezone.
            $timestamp = new DateTimeImmutable($value);
            return preg_match('/(?:Z|[+-][0-9]{2}:[0-9]{2})$/i', trim($value)) === 1
                && $timestamp->getTimestamp() > 0;
        } catch (\Throwable) {
            return false;
        }
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

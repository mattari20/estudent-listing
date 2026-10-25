<?php

namespace App\Services;

/**
 * Produces a reviewable field-level diff. It never writes canonical records.
 */
class InstituteDiscoveryFieldDiffService
{
    /**
     * @param array<string, mixed> $canonical
     * @param array<string, mixed> $candidate
     * @param array<string, array<string, mixed>> $evidenceByField
     * @param array<string> $sensitiveFields
     * @return array{state:string,changes:array<string, array<string, mixed>>,unchanged:array<string>,blockers:array<string>}
     */
    public function compare(
        array $canonical,
        array $candidate,
        array $evidenceByField = [],
        array $sensitiveFields = ['institution_id', 'campus_id', 'status', 'delivery_mode', 'accreditation']
    ): array {
        $changes = [];
        $unchanged = [];
        $blockers = [];

        foreach ($candidate as $field => $proposedValue) {
            if (str_starts_with((string) $field, '_')) {
                continue;
            }

            $currentValue = $canonical[$field] ?? null;
            if ($this->normalize($currentValue) === $this->normalize($proposedValue)) {
                $unchanged[] = (string) $field;
                continue;
            }

            $evidence = $evidenceByField[$field] ?? [];
            $url = trim((string) ($evidence['url'] ?? ''));
            $excerpt = trim((string) ($evidence['excerpt'] ?? ''));
            $official = ($evidence['official'] ?? false) === true;
            $validUrl = filter_var($url, FILTER_VALIDATE_URL)
                && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);

            $fieldBlockers = [];
            $verifiedOfficialEvidence = $official
                && ($evidence['source_identity_verified'] ?? false) === true
                && ($evidence['fetch_successful'] ?? false) === true
                && ($evidence['entity_match_confirmed'] ?? false) === true
                && trim((string) ($evidence['observed_at'] ?? '')) !== ''
                && trim((string) ($evidence['content_fingerprint'] ?? '')) !== '';
            if (! $validUrl) {
                $fieldBlockers[] = 'field_evidence_url_missing_or_invalid';
            }
            if ($excerpt === '') {
                $fieldBlockers[] = 'field_evidence_excerpt_missing';
            }
            if (in_array((string) $field, $sensitiveFields, true) && ! $official) {
                $fieldBlockers[] = 'official_evidence_required_for_sensitive_field';
            }

            $changes[$field] = [
                'previous_value' => $currentValue,
                'proposed_value' => $proposedValue,
                'evidence_url' => $validUrl ? $url : null,
                'evidence_excerpt' => $excerpt !== '' ? $excerpt : null,
                'source_official' => $official,
                'source_identity_verified' => ($evidence['source_identity_verified'] ?? false) === true,
                'fetch_successful' => ($evidence['fetch_successful'] ?? false) === true,
                'entity_match_confirmed' => ($evidence['entity_match_confirmed'] ?? false) === true,
                'observed_at' => $evidence['observed_at'] ?? null,
                'content_fingerprint' => $evidence['content_fingerprint'] ?? null,
                'state' => $fieldBlockers !== []
                    ? 'blocked'
                    : ($verifiedOfficialEvidence ? 'refresh_eligible' : 'review_required'),
                'blockers' => $fieldBlockers,
            ];

            foreach ($fieldBlockers as $blocker) {
                $blockers[] = $field.':'.$blocker;
            }
        }

        $state = $blockers !== []
            ? 'blocked'
            : ($changes === [] ? 'no_change' : (
                count(array_filter($changes, fn (array $change): bool => $change['state'] !== 'refresh_eligible')) === 0
                    ? 'refresh_eligible'
                    : 'review_required'
            ));

        return [
            'state' => $state,
            'changes' => $changes,
            'unchanged' => $unchanged,
            'blockers' => array_values(array_unique($blockers)),
        ];
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

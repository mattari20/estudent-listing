<?php

namespace App\Services;

/**
 * Deterministic structural checks for enrichment payloads before any canonical write.
 * This is deliberately separate from evidence/review policy.
 */
class StructuredEnrichmentPayloadValidator
{
    /**
     * @param array<string, mixed> $payload
     * @return list<string>
     */
    public function blockers(string $entityType, array $payload): array
    {
        $required = match ($entityType) {
            'campus' => ['institution_id', 'name', 'slug'],
            'program' => ['institution_id', 'name', 'slug'],
            'offering' => ['program_id', 'campus_id', 'delivery_mode'],
            default => ['__unsupported_entity_type'],
        };

        $blockers = [];
        foreach ($required as $field) {
            if ($field === '__unsupported_entity_type') {
                $blockers[] = 'unsupported_entity_type';
                continue;
            }

            if (! array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                $blockers[] = $field.':required';
                continue;
            }

            if (in_array($field, ['institution_id', 'program_id', 'campus_id'], true)
                && (! is_numeric($payload[$field]) || (int) $payload[$field] < 1)) {
                $blockers[] = $field.':must_be_positive_id';
            }
        }

        foreach (['name', 'slug', 'delivery_mode'] as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] !== null
                && (! is_string($payload[$field]) || trim($payload[$field]) === '')) {
                $blockers[] = $field.':must_be_non_empty_string';
            }
        }

        return array_values(array_unique($blockers));
    }
}

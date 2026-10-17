<?php

namespace App\Services;

use RuntimeException;

class EnrichmentImportSchemaService
{
    public function profile(string $entityType): array
    {
        return match ($entityType) {
            'campus' => [
                'version' => '1.0',
                'columns' => ['name', 'slug', 'campus_type', 'status', 'geography_id', 'address', 'postal_code', 'latitude', 'longitude'],
                'required' => ['name'],
            ],
            'program' => [
                'version' => '1.0',
                'columns' => ['name', 'slug', 'program_level', 'award', 'discipline', 'status', 'description'],
                'required' => ['name'],
            ],
            default => throw new RuntimeException("Unsupported enrichment entity type: {$entityType}"),
        };
    }

    public function validateHeaders(array $headers, string $entityType): array
    {
        $profile = $this->profile($entityType);
        $headers = array_values(array_unique(array_map(fn ($value) => trim((string) $value), $headers)));
        $errors = [];

        foreach ($headers as $header) {
            if (! in_array($header, $profile['columns'], true)) {
                $errors[] = "Unsupported column: {$header}";
            }
        }

        foreach ($profile['required'] as $required) {
            if (! in_array($required, $headers, true)) {
                $errors[] = "Required column missing: {$required}";
            }
        }

        return $errors;
    }

    public function normalize(array $payload, string $entityType): array
    {
        foreach ($payload as $key => $value) {
            if (is_string($value)) {
                $payload[$key] = trim(preg_replace('/\\s+/u', ' ', $value));
            }
        }

        $payload['name'] = trim((string) ($payload['name'] ?? ''));

        if (empty($payload['slug'])) {
            $payload['slug'] = str()->slug($payload['name']);
        } else {
            $payload['slug'] = str()->slug((string) $payload['slug']);
        }

        if ($entityType === 'campus') {
            foreach (['campus_type', 'status', 'address', 'postal_code'] as $field) {
                if (array_key_exists($field, $payload) && $payload[$field] !== null) {
                    $payload[$field] = trim((string) $payload[$field]);
                }
            }
        }

        if ($entityType === 'program') {
            foreach (['program_level', 'award', 'discipline', 'status', 'description'] as $field) {
                if (array_key_exists($field, $payload) && $payload[$field] !== null) {
                    $payload[$field] = trim((string) $payload[$field]);
                }
            }
        }

        return $payload;
    }

    public function validateRow(array $payload, string $entityType, array $seenSlugs): array
    {
        $errors = [];

        if (($payload['name'] ?? '') === '') {
            $errors[] = 'name is required';
        }

        $slug = (string) ($payload['slug'] ?? '');
        if ($slug !== '' && isset($seenSlugs[$slug])) {
            $errors[] = 'duplicate slug within import batch';
        }

        if ($entityType === 'campus') {
            if (($payload['geography_id'] ?? '') !== '' && (! ctype_digit((string) $payload['geography_id']) || (int) $payload['geography_id'] < 1)) {
                $errors[] = 'geography_id must be a positive integer';
            }

            foreach (['latitude', 'longitude'] as $field) {
                if (($payload[$field] ?? '') !== '' && ! is_numeric($payload[$field])) {
                    $errors[] = "{$field} must be numeric";
                }
            }
        }

        return $errors;
    }

    public function template(string $entityType): string
    {
        return implode(',', $this->profile($entityType)['columns']) . "\n";
    }
}

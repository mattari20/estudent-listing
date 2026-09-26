<?php

namespace App\Services;

use App\Contracts\InstituteAiExtractionProvider;
use Illuminate\Support\Str;

class InstituteAiExtractionService
{
    public function __construct(
        private readonly InstituteAiExtractionProvider $provider,
    ) {
    }

    public function extract(string $sourceContent, array $context = []): array
    {
        if (! config('institute_ai.enabled')) {
            throw new \RuntimeException('Institute AI extraction is disabled. Set INSTITUTE_AI_ENABLED=true to use it.');
        }

        $candidates = $this->provider->extract($sourceContent, $context);
        $normalized = [];

        foreach ($candidates as $candidate) {
            $name = trim((string) ($candidate['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $confidence = max(0, min(1, (float) ($candidate['confidence'] ?? 0)));

            $normalized[Str::slug($name)] = [
                'name' => $name,
                'award' => $this->nullableString($candidate['award'] ?? null),
                'discipline' => $this->nullableString($candidate['discipline'] ?? null),
                'program_level' => $this->normalizeLevel($candidate['program_level'] ?? null),
                'status' => $this->normalizeStatus($candidate['status'] ?? null),
                'confidence' => $confidence,
                'evidence_excerpt' => trim((string) ($candidate['evidence_excerpt'] ?? '')),
            ];
        }

        return array_values($normalized);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || strtolower($value) === 'null' ? null : $value;
    }

    private function normalizeLevel(mixed $value): string
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, ['undergraduate', 'graduate', 'postgraduate', 'phd', 'diploma', 'certificate', 'other'], true)
            ? $value
            : 'other';
    }

    private function normalizeStatus(mixed $value): string
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, ['active', 'inactive'], true) ? $value : 'unknown';
    }
}

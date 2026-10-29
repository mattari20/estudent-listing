<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Program;
use Illuminate\Support\Str;

class ImportDuplicateDetectorService
{
    public function find(string $entityType, int $parentInstitutionId, string $slug, ?int $currentBatchId = null, array $ignoredBatchIds = []): ?array
    {
        if ($slug === '') {
            return null;
        }

        $canonical = match ($entityType) {
            'campus' => Campus::query()->where('institution_id', $parentInstitutionId)->where('slug', $slug)->first(),
            'program' => Program::query()->where('institution_id', $parentInstitutionId)->where('slug', $slug)->first(),
            default => null,
        };

        if ($canonical) {
            return [
                'kind' => 'canonical',
                'label' => class_basename($canonical),
                'id' => $canonical->id,
                'message' => 'This record already exists in canonical data. It will not overwrite the existing record automatically.',
            ];
        }

        $previous = ImportRow::query()
            ->where('parent_subject_type', Institution::class)
            ->where('parent_subject_id', $parentInstitutionId)
            ->where('normalized_payload->slug', $slug)
            ->whereIn('status', ['candidate', 'promoted', 'duplicate'])
            ->whereHas('batch', fn ($query) => $query->where('entity_type', $entityType))
            ->when($currentBatchId, fn ($query) => $query->where('import_batch_id', '!=', $currentBatchId))
            ->when($ignoredBatchIds !== [], fn ($query) => $query->whereNotIn('import_batch_id', $ignoredBatchIds))
            ->latest('id')
            ->first();

        if ($previous) {
            return [
                'kind' => 'import',
                'label' => 'Import Row',
                'id' => $previous->id,
                'message' => 'The same record was already imported previously. Review the existing import row instead of creating another copy.',
            ];
        }

        return null;
    }

    /**
     * Resolve a top-level institution identity without assuming a canonical parent.
     * Existing canonical or staged identities are reported for review, never overwritten.
     */
    public function findInstitution(string $slug, ?int $currentBatchId = null, array $ignoredBatchIds = [], array $identity = []): ?array
    {
        $candidateSlug = $this->normalizedIdentityKey($slug);
        if ($candidateSlug === '') {
            return null;
        }

        // Keep canonical matching on the configured slug; name fallbacks below
        // apply only to staged rows and never mutate canonical data.
        $canonical = Institution::query()->where('slug', $slug)->first();
        if ($canonical) {
            $candidateOfficial = $this->normalizedIdentityKey((string) ($identity['official_name'] ?? ''));
            $canonicalOfficial = $this->normalizedIdentityKey((string) ($canonical->official_name ?? ''));

            // A matching slug is not sufficient when two known official names disagree.
            if ($candidateOfficial === '' || $canonicalOfficial === '' || $candidateOfficial === $canonicalOfficial) {
                return [
                    'kind' => 'canonical',
                    'label' => 'Institution',
                    'id' => $canonical->id,
                    'message' => 'This institution already exists in canonical data. Review the existing record instead of creating another copy.',
                ];
            }
        }

        // Stream historical institution rows rather than relying on a JSON-path
        // equality that may miss legacy payload variants. When both official names
        // exist they must match; a shared generic display name alone is insufficient.
        $previousRows = ImportRow::query()
            ->whereIn('status', ['candidate', 'promoted', 'duplicate'])
            ->whereHas('batch', fn ($query) => $query->where('entity_type', 'institution'))
            ->when($currentBatchId, fn ($query) => $query->where('import_batch_id', '!=', $currentBatchId))
            ->when($ignoredBatchIds !== [], fn ($query) => $query->whereNotIn('import_batch_id', $ignoredBatchIds))
            ->latest('id')
            ->cursor();

        foreach ($previousRows as $previous) {
            $payload = is_array($previous->normalized_payload) ? $previous->normalized_payload : [];
            if ($payload === []) {
                $payload = is_array($previous->payload) ? $previous->payload : [];
            }
            if (! $this->sameInstitutionIdentity($slug, $identity, $payload)) {
                continue;
            }

            return [
                'kind' => 'import',
                'label' => 'Import Row',
                'id' => $previous->id,
                'message' => 'The same normalized institution identity was already staged. Review the existing import row instead of creating another copy.',
            ];
        }

        return null;
    }

    private function sameInstitutionIdentity(string $slug, array $candidate, array $previous): bool
    {
        $candidateOfficial = $this->normalizedIdentityKey((string) ($candidate['official_name'] ?? ''));
        $previousOfficial = $this->normalizedIdentityKey((string) ($previous['official_name'] ?? ''));

        // Known official names take precedence over a shared slug or generic display name.
        if ($candidateOfficial !== '' && $previousOfficial !== '') {
            return $candidateOfficial === $previousOfficial;
        }

        $candidateSlug = $this->normalizedIdentityKey($slug);
        $previousSlug = $this->normalizedIdentityKey((string) ($previous['slug'] ?? ''));
        if ($candidateSlug !== '' && $candidateSlug === $previousSlug) {
            return true;
        }

        $candidateName = $this->normalizedIdentityKey((string) ($candidate['name'] ?? ''));
        $previousName = $this->normalizedIdentityKey((string) ($previous['name'] ?? ''));
        if ($candidateName === '' || $candidateName !== $previousName) {
            return false;
        }

        $candidateType = Str::lower(trim((string) ($candidate['institution_type'] ?? '')));
        $previousType = Str::lower(trim((string) ($previous['institution_type'] ?? '')));

        return $candidateType === '' || $previousType === '' || $candidateType === $previousType;
    }

    private function normalizedIdentityKey(string $value): string
    {
        return $value === '' ? '' : Str::lower(Str::slug(trim($value)));
    }

}

<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Program;

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
    public function findInstitution(string $slug, ?int $currentBatchId = null, array $ignoredBatchIds = []): ?array
    {
        if ($slug === '') {
            return null;
        }

        $canonical = Institution::query()->where('slug', $slug)->first();
        if ($canonical) {
            return [
                'kind' => 'canonical',
                'label' => 'Institution',
                'id' => $canonical->id,
                'message' => 'This institution already exists in canonical data. Review the existing record instead of creating another copy.',
            ];
        }

        $previous = ImportRow::query()
            ->where('normalized_payload->slug', $slug)
            ->whereIn('status', ['candidate', 'promoted', 'duplicate'])
            ->whereHas('batch', fn ($query) => $query->where('entity_type', 'institution'))
            ->when($currentBatchId, fn ($query) => $query->where('import_batch_id', '!=', $currentBatchId))
            ->when($ignoredBatchIds !== [], fn ($query) => $query->whereNotIn('import_batch_id', $ignoredBatchIds))
            ->latest('id')
            ->first();

        if ($previous) {
            return [
                'kind' => 'import',
                'label' => 'Import Row',
                'id' => $previous->id,
                'message' => 'The same institution identity was already staged. Review the existing import row instead of creating another copy.',
            ];
        }

        return null;
    }

}

<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Program;

class ImportDuplicateDetectorService
{
    public function find(string $entityType, int $parentInstitutionId, string $slug, ?int $currentBatchId = null): ?array
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
            ->whereJsonPathText('normalized_payload', '$.slug', '=', $slug)
            ->whereHas('batch', fn ($query) => $query->where('entity_type', $entityType))
            ->when($currentBatchId, fn ($query) => $query->where('import_batch_id', '!=', $currentBatchId))
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
}

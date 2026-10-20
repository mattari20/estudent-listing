<?php
namespace App\Services;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use RuntimeException;

class StructuredEnrichmentService
{
    public function promote(ImportRow $row): object
    {
        if ($row->match_state === 'duplicate') {
            throw new RuntimeException('This import row is marked as a duplicate and cannot overwrite existing canonical data.');
        }
        if ($row->status !== 'candidate') {
            throw new RuntimeException('Only candidate import rows can be promoted.');
        }

        $payload = $row->normalized_payload ?? $row->payload ?? [];
        $type = $row->batch->entity_type;
        $parent = $this->parent($row);
        $entity = match ($type) {
            'campus' => $this->campus($payload, $parent),
            'program' => $this->program($payload, $parent),
            'program_offering' => $this->offering($payload, $parent),
            default => throw new RuntimeException('Unsupported enrichment entity type.'),
        };

        $row->update([
            'status' => 'promoted',
            'match_state' => 'enriched_canonical',
            'canonical_subject_type' => $entity::class,
            'canonical_subject_id' => $entity->id,
        ]);

        return $entity;
    }

    private function parent(ImportRow $row): Institution|Program
    {
        if (!$row->parent_subject_type || !$row->parent_subject_id) {
            throw new RuntimeException('A canonical parent is required.');
        }

        return $row->parent_subject_type === Institution::class
            ? Institution::findOrFail($row->parent_subject_id)
            : Program::findOrFail($row->parent_subject_id);
    }

    private function campus(array $payload, Institution $institution): Campus
    {
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Campus name is required.');
        }

        $slug = $payload['slug'] ?? str()->slug($name);
        $geographyId = $payload['geography_id'] ?? null;

        if ($geographyId !== null && $geographyId !== '') {
            $geography = Geography::query()
                ->whereKey($geographyId)
                ->where('status', 'active')
                ->first();

            if (!$geography) {
                throw new RuntimeException('The selected geography does not exist or is not active.');
            }

            $geographyId = $geography->id;
        } else {
            $geographyId = null;
        }

        $existing = Campus::query()
            ->where('institution_id', $institution->id)
            ->where('slug', $slug)
            ->first();

        if ($existing) {
            throw new RuntimeException('This campus already exists in canonical data (ID '.$existing->id.'). The import will not overwrite it. Reject this duplicate row or review the existing campus.');
        }

        return Campus::create([
            'institution_id' => $institution->id,
            'slug' => $slug,
            'name' => $name,
            'campus_type' => $payload['campus_type'] ?? null,
            'status' => $payload['status'] ?? 'active',
            'geography_id' => $geographyId,
            'address' => $payload['address'] ?? null,
            'postal_code' => $payload['postal_code'] ?? null,
            'latitude' => ($payload['latitude'] ?? null) === '' ? null : ($payload['latitude'] ?? null),
            'longitude' => ($payload['longitude'] ?? null) === '' ? null : ($payload['longitude'] ?? null),
        ]);
    }

    private function program(array $payload, Institution $institution): Program
    {
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Program name is required.');
        }

        $slug = $payload['slug'] ?? str()->slug($name);
        $existing = Program::query()
            ->where('institution_id', $institution->id)
            ->where('slug', $slug)
            ->first();

        if ($existing) {
            throw new RuntimeException('This program already exists in canonical data (ID '.$existing->id.'). The import will not overwrite it. Reject this duplicate row or review the existing program.');
        }

        return Program::create([
            'institution_id' => $institution->id,
            'slug' => $slug,
            'name' => $name,
            'program_level' => $payload['program_level'] ?? null,
            'award' => $payload['award'] ?? null,
            'discipline' => $payload['discipline'] ?? null,
            'status' => $payload['status'] ?? 'active',
            'description' => $payload['description'] ?? null,
        ]);
    }

    private function offering(array $payload, Program $program): ProgramOffering
    {
        $campusId = $payload['campus_id'] ?? null;
        if ($campusId !== null && $campusId !== '') {
            $campus = Campus::query()->findOrFail($campusId);
            if ((int) $campus->institution_id !== (int) $program->institution_id) {
                throw new RuntimeException('The selected campus does not belong to the institution that owns this program.');
            }
            $campusId = $campus->id;
        } else {
            $campusId = null;
        }

        return ProgramOffering::firstOrCreate(
            [
                'program_id' => $program->id,
                'campus_id' => $campusId,
                'delivery_mode' => $payload['delivery_mode'] ?? null,
                'intake_label' => $payload['intake_label'] ?? null,
            ],
            [
                'status' => $payload['status'] ?? 'active',
                'duration' => $payload['duration'] ?? null,
                'availability' => $payload['availability'] ?? null,
                'effective_from' => $payload['effective_from'] ?? null,
                'effective_until' => $payload['effective_until'] ?? null,
            ],
        );
    }
}

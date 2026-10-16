<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\Institution;
use Illuminate\Support\Collection;

class CanonicalGeographyIntegrityService
{
    /**
     * Return canonical geography integrity findings without mutating data.
     *
     * Findings are deliberately descriptive so operators can review them
     * before any correction or promotion is performed.
     */
    public function inspectInstitution(Institution $institution): Collection
    {
        $institution->loadMissing([
            'campuses' => fn ($query) => $query->with('geography'),
        ]);

        return $institution->campuses
            ->filter(fn (Campus $campus) => $campus->status === 'active')
            ->map(fn (Campus $campus) => $this->inspectCampus($campus))
            ->filter()
            ->values();
    }

    public function inspectCampus(Campus $campus): ?array
    {
        $campus->loadMissing('institution', 'geography');

        if ($campus->status !== 'active') {
            return null;
        }

        if (! $campus->institution) {
            return [
                'type' => 'missing_institution',
                'campus_id' => $campus->id,
                'message' => 'Campus has no canonical institution.',
            ];
        }

        if (! $campus->geography) {
            return [
                'type' => 'missing_geography',
                'campus_id' => $campus->id,
                'institution_id' => $campus->institution_id,
                'message' => 'Active campus has no canonical geography.',
            ];
        }

        if ($campus->geography->status !== 'active') {
            return [
                'type' => 'inactive_geography',
                'campus_id' => $campus->id,
                'institution_id' => $campus->institution_id,
                'geography_id' => $campus->geography_id,
                'message' => 'Active campus points to inactive geography.',
            ];
        }

        return null;
    }
}

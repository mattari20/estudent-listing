<?php

namespace App\Services;

use App\Models\Institution;
use Illuminate\Support\Collection;

class InstitutionGeographyIntegrityService
{
    /**
     * Inspect the geography coverage of an institution without mutating data.
     *
     * Findings are descriptive and intentionally do not imply automatic repair.
     */
    public function inspect(Institution $institution): Collection
    {
        if ($institution->status !== 'active') {
            return collect();
        }

        $institution->loadMissing([
            'campuses' => fn ($query) => $query->with('geography'),
        ]);

        $activeCampuses = $institution->campuses
            ->filter(fn ($campus) => $campus->status === 'active')
            ->values();

        if ($activeCampuses->isEmpty()) {
            return collect([[
                'type' => 'missing_active_campus',
                'institution_id' => $institution->id,
                'message' => 'Active institution has no active campus.',
            ]]);
        }

        $activeGeographyCampuses = $activeCampuses
            ->filter(fn ($campus) => $campus->geography && $campus->geography->status === 'active');

        if ($activeGeographyCampuses->isEmpty()) {
            return collect([[
                'type' => 'missing_active_geography',
                'institution_id' => $institution->id,
                'active_campus_count' => $activeCampuses->count(),
                'message' => 'Active institution has no active campus linked to active geography.',
            ]]);
        }

        return collect();
    }
}

<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\Institution;
use Illuminate\Support\Collection;

class InstituteGeographyIntegrityService
{
    public function inspectInstitution(Institution $institution): Collection
    {
        if ($institution->status !== 'active') {
            return collect();
        }

        $institution->loadMissing('campuses');
        $campuses = $institution->campuses->where('status', 'active')->values();

        if ($campuses->isEmpty()) {
            return collect([[
                'type' => 'missing_active_campus',
                'institution_id' => $institution->id,
                'message' => 'Active institution has no active campus.',
            ]]);
        }

        $findings = collect();

        foreach ($campuses as $campus) {
            $findings = $findings->merge($this->inspectCampus($campus));
        }

        if (! $findings->contains(fn ($finding) => $finding['type'] === 'valid_geography')) {
            $findings->prepend([
                'type' => 'missing_active_geography',
                'institution_id' => $institution->id,
                'active_campus_count' => $campuses->count(),
                'message' => 'Active institution has no active campus with a complete active geography hierarchy.',
            ]);
        }

        return $findings->values();
    }

    public function inspectCampus(Campus $campus): Collection
    {
        if ($campus->status !== 'active') {
            return collect();
        }

        $campus->loadMissing('geography');

        if ($campus->geography === null) {
            return collect([[
                'type' => 'missing_geography',
                'campus_id' => $campus->id,
                'institution_id' => $campus->institution_id,
                'message' => 'Active campus has no canonical geography.',
            ]]);
        }

        $seen = [];
        $geography = $campus->geography;

        while ($geography !== null) {
            if (isset($seen[$geography->id])) {
                return collect([[
                    'type' => 'geography_cycle',
                    'campus_id' => $campus->id,
                    'institution_id' => $campus->institution_id,
                    'geography_id' => $geography->id,
                    'message' => 'Campus geography hierarchy contains a cycle.',
                ]]);
            }

            $seen[$geography->id] = true;

            if ($geography->status !== 'active') {
                return collect([[
                    'type' => 'inactive_geography_ancestor',
                    'campus_id' => $campus->id,
                    'institution_id' => $campus->institution_id,
                    'geography_id' => $geography->id,
                    'message' => 'Campus geography hierarchy contains inactive geography.',
                ]]);
            }

            if ($geography->parent_id === null) {
                return collect([[
                    'type' => 'valid_geography',
                    'campus_id' => $campus->id,
                    'institution_id' => $campus->institution_id,
                    'geography_id' => $campus->geography_id,
                ]]);
            }

            $geography->loadMissing('parent');

            if ($geography->parent === null) {
                return collect([[
                    'type' => 'missing_geography_parent',
                    'campus_id' => $campus->id,
                    'institution_id' => $campus->institution_id,
                    'geography_id' => $geography->id,
                    'parent_id' => $geography->parent_id,
                    'message' => 'Campus geography references a missing parent.',
                ]]);
            }

            $geography = $geography->parent;
        }

        return collect();
    }
}

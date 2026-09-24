<?php

namespace App\Services;

use App\Models\Campus;
use Illuminate\Support\Collection;

class CampusGeographyIntegrityService
{
    /**
     * Inspect one campus without mutating canonical data.
     *
     * Active campus geography is valid only when the assigned geography and
     * every canonical geography ancestor are active. Root geography is valid
     * without a parent.
     */
    public function inspect(Campus $campus): Collection
    {
        if ($campus->status !== 'active') {
            return collect();
        }

        $campus->loadMissing('geography');

        if (! $campus->geography) {
            return collect([[
                'type' => 'missing_geography',
                'campus_id' => $campus->id,
                'institution_id' => $campus->institution_id,
                'message' => 'Active campus has no canonical geography.',
            ]]);
        }

        if ($campus->geography->status !== 'active') {
            return collect([[
                'type' => 'inactive_geography',
                'campus_id' => $campus->id,
                'institution_id' => $campus->institution_id,
                'geography_id' => $campus->geography_id,
                'message' => 'Active campus points to inactive geography.',
            ]]);
        }

        $seen = [];
        $geography = $campus->geography;

        while ($geography->parent_id !== null) {
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
            $geography->loadMissing('parent');
            $parent = $geography->parent;

            if (! $parent) {
                return collect([[
                    'type' => 'missing_geography_parent',
                    'campus_id' => $campus->id,
                    'institution_id' => $campus->institution_id,
                    'geography_id' => $geography->id,
                    'parent_id' => $geography->parent_id,
                    'message' => 'Campus geography references a missing parent geography.',
                ]]);
            }

            if ($parent->status !== 'active') {
                return collect([[
                    'type' => 'inactive_geography_parent',
                    'campus_id' => $campus->id,
                    'institution_id' => $campus->institution_id,
                    'geography_id' => $geography->id,
                    'parent_id' => $parent->id,
                    'message' => 'Campus geography has an inactive canonical parent.',
                ]]);
            }

            $geography = $parent;
        }

        return collect();
    }
}

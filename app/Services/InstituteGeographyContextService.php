<?php

namespace App\Services;

use App\Models\Campus;

class InstituteGeographyContextService
{
    public function forCampus(Campus $campus): ?string
    {
        if ($campus->status !== 'active') {
            return null;
        }

        $campus->loadMissing('geography');

        $parts = [];
        $seen = [];
        $geography = $campus->geography;

        while ($geography !== null) {
            if ($geography->status !== 'active' || isset($seen[$geography->id])) {
                return null;
            }

            $seen[$geography->id] = true;
            array_unshift($parts, $geography->name);
            $geography->loadMissing('parent');
            $geography = $geography->parent;
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }
}

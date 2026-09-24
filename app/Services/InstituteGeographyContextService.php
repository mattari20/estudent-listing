<?php

namespace App\Services;

use App\Models\Campus;

class InstituteGeographyContextService
{
    /**
     * Build a human-readable root-to-leaf geography context for a public campus.
     *
     * Inactive ancestors are excluded from public context rather than silently
     * presented as current geography.
     */
    public function forCampus(Campus $campus): ?string
    {
        if ($campus->status !== 'active' || ! $campus->geography) {
            return null;
        }

        $parts = [];
        $seen = [];
        $geography = $campus->geography;

        while ($geography) {
            if ($geography->status !== 'active' || isset($seen[$geography->id])) {
                return null;
            }

            $seen[$geography->id] = true;
            array_unshift($parts, $geography->name);

            $geography->loadMissing('parent');
            $geography = $geography->parent;
        }

        return implode(' · ', $parts);
    }
}

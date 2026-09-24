<?php

namespace App\Services;

use App\Models\Campus;
use Illuminate\Support\Collection;

class GeographyGapDetectionService
{
    /**
     * Find active campuses whose canonical geography linkage is incomplete.
     *
     * This is a read-only detection boundary intended for later operator
     * review. It does not repair or reassign canonical geography.
     */
    public function campuses(): Collection
    {
        return Campus::query()
            ->where('status', 'active')
            ->with(['institution', 'geography'])
            ->get()
            ->map(function (Campus $campus): ?array {
                if (! $campus->geography) {
                    return [
                        'type' => 'missing_geography',
                        'campus_id' => $campus->id,
                        'institution_id' => $campus->institution_id,
                    ];
                }

                if ($campus->geography->status !== 'active') {
                    return [
                        'type' => 'inactive_geography',
                        'campus_id' => $campus->id,
                        'institution_id' => $campus->institution_id,
                        'geography_id' => $campus->geography_id,
                    ];
                }

                return null;
            })
            ->filter()
            ->values();
    }
}

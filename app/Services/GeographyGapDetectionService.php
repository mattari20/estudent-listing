<?php

namespace App\Services;

use App\Models\Campus;
use Illuminate\Support\Collection;

class GeographyGapDetectionService
{
    public function campuses(): Collection
    {
        return Campus::query()
            ->where('status', 'active')
            ->with(['institution', 'geography'])
            ->get()
            ->map(function (Campus $campus): ?array {
                if ($campus->geography === null) {
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
            ->filter(fn (?array $finding): bool => $finding !== null)
            ->values();
    }
}

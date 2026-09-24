<?php

namespace App\Services;

use App\Models\Institution;
use Illuminate\Support\Collection;

class InstituteGeographyCrossDomainIntegrityService
{
    public function __construct(
        private readonly InstitutionGeographyIntegrityService $institutionIntegrity,
        private readonly CampusGeographyIntegrityService $campusIntegrity,
    ) {
    }

    /**
     * Combine institution- and campus-level geography integrity findings.
     *
     * The service is read-only and intentionally delegates individual rules
     * to their existing bounded services.
     */
    public function inspect(Institution $institution): Collection
    {
        if ($institution->status !== 'active') {
            return collect();
        }

        $findings = $this->institutionIntegrity->inspect($institution);

        $institution->loadMissing('campuses');

        foreach ($institution->campuses->where('status', 'active') as $campus) {
            $findings = $findings->merge($this->campusIntegrity->inspect($campus));
        }

        return $findings->values();
    }
}

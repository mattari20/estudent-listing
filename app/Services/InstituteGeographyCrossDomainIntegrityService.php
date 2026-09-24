<?php

namespace App\Services;

use App\Models\Institution;
use Illuminate\Support\Collection;

class InstituteGeographyCrossDomainIntegrityService
{
    public function __construct(
        private readonly InstituteGeographyIntegrityService $integrity,
    ) {}

    public function inspect(Institution $institution): Collection
    {
        return $this->integrity->inspectInstitution($institution);
    }
}

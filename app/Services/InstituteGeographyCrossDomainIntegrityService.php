<?php

namespace AppServices;

use AppModelsInstitution;
use IlluminateSupportCollection;

class InstituteGeographyCrossDomainIntegrityService
{
    public function __construct(
        private readonly InstituteGeographyIntegrityService $integrity,
    ) {
    }

    public function inspect(Institution $institution): Collection
    {
        return $this->integrity->inspectInstitution($institution);
    }
}

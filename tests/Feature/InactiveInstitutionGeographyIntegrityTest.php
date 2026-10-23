<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Services\InstituteGeographyIntegrityService;
use Tests\TestCase;

class InactiveInstitutionGeographyIntegrityTest extends TestCase
{
    public function test_inactive_institution_has_no_geography_integrity_findings(): void
    {
        $institution = new Institution(['status' => 'inactive']);
        $this->assertCount(0, app(InstituteGeographyIntegrityService::class)->inspectInstitution($institution));
    }
}

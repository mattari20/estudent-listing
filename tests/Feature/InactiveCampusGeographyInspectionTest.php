<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Services\InstituteGeographyIntegrityService;
use Tests\TestCase;

class InactiveCampusGeographyInspectionTest extends TestCase
{
    public function test_inactive_campus_returns_no_geography_findings(): void
    {
        $campus = new Campus(['status' => 'inactive']);
        $this->assertCount(0, app(InstituteGeographyIntegrityService::class)->inspectCampus($campus));
    }
}

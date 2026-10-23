<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Services\GeographyGapDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveCampusValidGeographyNoGapTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_campus_with_active_geography_is_not_reported_as_a_gap(): void
    {
        $country = Geography::create([
            'name' => 'Coverage Test Country',
            'slug' => 'coverage-test-country',
            'type' => 'country',
            'status' => 'active',
        ]);
        $province = Geography::create([
            'name' => 'Coverage Test Province',
            'slug' => 'coverage-test-province',
            'type' => 'province',
            'parent_id' => $country->id,
            'status' => 'active',
        ]);
        $institution = Institution::create([
            'name' => 'Valid Geography University',
            'slug' => 'valid-geography-university',
            'status' => 'active',
        ]);
        Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Valid Geography Campus',
            'slug' => 'valid-geography-campus',
            'status' => 'active',
            'geography_id' => $province->id,
        ]);

        $findings = app(GeographyGapDetectionService::class)->campuses();

        $this->assertCount(0, $findings);
    }
}

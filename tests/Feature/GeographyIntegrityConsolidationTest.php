<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Services\InstituteGeographyIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyIntegrityConsolidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_campus_with_active_geography_is_valid(): void
    {
        $geography = Geography::create([
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'city',
            'status' => 'active',
        ]);

        $institution = Institution::create([
            'name' => 'Geography University',
            'slug' => 'geography-university',
            'status' => 'active',
        ]);

        $campus = Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $geography->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $this->assertSame(
            'valid_geography',
            (new InstituteGeographyIntegrityService)->inspectCampus($campus)->first()['type']
        );
    }

    public function test_inactive_geography_is_reported(): void
    {
        $geography = Geography::create([
            'name' => 'Legacy Area',
            'slug' => 'legacy-area',
            'type' => 'city',
            'status' => 'inactive',
        ]);

        $institution = Institution::create([
            'name' => 'Legacy University',
            'slug' => 'legacy-university',
            'status' => 'active',
        ]);

        $campus = Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $geography->id,
            'name' => 'Main Campus',
            'slug' => 'legacy-campus',
            'status' => 'active',
        ]);

        $this->assertSame(
            'inactive_geography_ancestor',
            (new InstituteGeographyIntegrityService)->inspectCampus($campus)->first()['type']
        );
    }

    public function test_active_institution_without_active_campus_is_reported(): void
    {
        $institution = Institution::create([
            'name' => 'Campusless University',
            'slug' => 'campusless-university',
            'status' => 'active',
        ]);

        $this->assertSame(
            'missing_active_campus',
            (new InstituteGeographyIntegrityService)->inspectInstitution($institution)->first()['type']
        );
    }
}

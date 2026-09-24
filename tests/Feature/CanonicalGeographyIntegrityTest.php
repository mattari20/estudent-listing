<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Services\CanonicalGeographyIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanonicalGeographyIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_campus_with_active_geography_has_no_integrity_finding(): void
    {
        $institution = Institution::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'institution_type' => 'university',
            'status' => 'active',
        ]);

        $geography = Geography::create([
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'district',
            'status' => 'active',
        ]);

        $campus = Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $geography->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $this->assertNull((new CanonicalGeographyIntegrityService)->inspectCampus($campus));
    }

    public function test_active_campus_without_geography_is_reported(): void
    {
        $institution = Institution::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'institution_type' => 'university',
            'status' => 'active',
        ]);

        $campus = Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $finding = (new CanonicalGeographyIntegrityService)->inspectCampus($campus);

        $this->assertSame('missing_geography', $finding['type']);
        $this->assertSame($campus->id, $finding['campus_id']);
    }

    public function test_active_campus_with_inactive_geography_is_reported(): void
    {
        $institution = Institution::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'institution_type' => 'university',
            'status' => 'active',
        ]);

        $geography = Geography::create([
            'name' => 'Legacy Area',
            'slug' => 'legacy-area',
            'type' => 'district',
            'status' => 'inactive',
        ]);

        $campus = Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $geography->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $finding = (new CanonicalGeographyIntegrityService)->inspectCampus($campus);

        $this->assertSame('inactive_geography', $finding['type']);
    }

    public function test_inactive_campus_is_not_flagged_for_geography_integrity(): void
    {
        $institution = Institution::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'institution_type' => 'university',
            'status' => 'active',
        ]);

        $campus = Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Archived Campus',
            'slug' => 'archived-campus',
            'status' => 'inactive',
        ]);

        $this->assertNull((new CanonicalGeographyIntegrityService)->inspectCampus($campus));
    }
}

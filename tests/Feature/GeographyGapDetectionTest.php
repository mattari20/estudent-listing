<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Services\GeographyGapDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyGapDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_campus_without_geography_is_detected(): void
    {
        $institution = Institution::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'status' => 'active',
        ]);

        $campus = Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $findings = (new GeographyGapDetectionService)->campuses();

        $this->assertCount(1, $findings);
        $this->assertSame('missing_geography', $findings->first()['type']);
        $this->assertSame($campus->id, $findings->first()['campus_id']);
    }

    public function test_active_campus_with_inactive_geography_is_detected(): void
    {
        $institution = Institution::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'status' => 'active',
        ]);

        $geography = Geography::create([
            'name' => 'Legacy Area',
            'slug' => 'legacy-area',
            'type' => 'city',
            'status' => 'inactive',
        ]);

        $campus = Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $geography->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $findings = (new GeographyGapDetectionService)->campuses();

        $this->assertSame('inactive_geography', $findings->first()['type']);
        $this->assertSame($campus->id, $findings->first()['campus_id']);
        $this->assertSame($geography->id, $findings->first()['geography_id']);
    }

    public function test_inactive_campus_is_not_detected(): void
    {
        $institution = Institution::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Archived Campus',
            'slug' => 'archived-campus',
            'status' => 'inactive',
        ]);

        $this->assertCount(0, (new GeographyGapDetectionService)->campuses());
    }

    public function test_active_campus_with_active_geography_is_not_detected(): void
    {
        $institution = Institution::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'status' => 'active',
        ]);

        $geography = Geography::create([
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'city',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $geography->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $this->assertCount(0, (new GeographyGapDetectionService)->campuses());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Institution;
use App\Services\GeographyGapDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveCampusMissingGeographyGapTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_campus_without_geography_is_reported_by_id(): void
    {
        $institution = Institution::create([
            'name' => 'Missing Geography University',
            'slug' => 'missing-geography-university',
            'status' => 'active',
        ]);
        $campus = Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Missing Geography Campus',
            'slug' => 'missing-geography-campus',
            'status' => 'active',
            'geography_id' => null,
        ]);

        $findings = app(GeographyGapDetectionService::class)->campuses();

        $this->assertTrue($findings->contains(fn (array $finding): bool =>
            $finding['type'] === 'missing_geography'
            && $finding['campus_id'] === $campus->id
            && $finding['institution_id'] === $institution->id
        ));
        $this->assertDatabaseHas('campuses', ['id' => $campus->id, 'geography_id' => null]);
    }
}

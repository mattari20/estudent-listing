<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Institution;
use App\Services\GeographyGapDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveCampusExcludedFromGeographyGapsTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_campus_without_geography_is_excluded_from_current_gap_results(): void
    {
        $institution = Institution::create([
            'name' => 'Inactive Campus Gap University',
            'slug' => 'inactive-campus-gap-university',
            'status' => 'active',
        ]);
        Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Inactive Campus',
            'slug' => 'inactive-campus',
            'status' => 'inactive',
            'geography_id' => null,
        ]);

        $this->assertCount(0, app(GeographyGapDetectionService::class)->campuses());
    }
}

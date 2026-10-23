<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Services\GeographyGapDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveCampusInactiveGeographyGapTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_campus_with_inactive_geography_is_reported(): void
    {
        $institution = Institution::create([
            'name' => 'Inactive Geography University',
            'slug' => 'inactive-geography-university',
            'status' => 'active',
        ]);
        $geography = Geography::create([
            'name' => 'Inactive Synthetic District',
            'slug' => 'inactive-synthetic-district',
            'type' => 'district',
            'status' => 'inactive',
        ]);
        $campus = Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Inactive Geography Campus',
            'slug' => 'inactive-geography-campus',
            'status' => 'active',
            'geography_id' => $geography->id,
        ]);

        $findings = app(GeographyGapDetectionService::class)->campuses();

        $this->assertTrue($findings->contains(fn (array $finding): bool =>
            $finding['type'] === 'inactive_geography'
            && $finding['campus_id'] === $campus->id
            && $finding['geography_id'] === $geography->id
        ));
    }
}

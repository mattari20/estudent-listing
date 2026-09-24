<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use App\Services\InstituteGeographyContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteGeographyContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_is_returned_root_to_leaf(): void
    {
        $province = Geography::create([
            'name' => 'Punjab',
            'slug' => 'punjab',
            'type' => 'province',
            'status' => 'active',
        ]);

        $district = Geography::create([
            'parent_id' => $province->id,
            'name' => 'Lahore District',
            'slug' => 'lahore',
            'type' => 'district',
            'status' => 'active',
        ]);

        $city = Geography::create([
            'parent_id' => $district->id,
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'city',
            'status' => 'active',
        ]);

        $institution = Institution::create([
            'name' => 'Example University',
            'slug' => 'example-university',
            'status' => 'active',
        ]);

        $campus = Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $city->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $this->assertSame(
            'Punjab · Lahore District · Lahore',
            (new InstituteGeographyContextService)->forCampus($campus)
        );
    }

    public function test_inactive_geography_does_not_produce_public_context(): void
    {
        $geography = Geography::create([
            'name' => 'Legacy Area',
            'slug' => 'legacy-area',
            'type' => 'city',
            'status' => 'inactive',
        ]);

        $institution = Institution::create([
            'name' => 'Example University',
            'slug' => 'example-university',
            'status' => 'active',
        ]);

        $campus = Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $geography->id,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);

        $this->assertNull((new InstituteGeographyContextService)->forCampus($campus));
    }
}

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
        $province = Geography::create(['name' => 'Punjab', 'slug' => 'punjab', 'type' => 'province', 'status' => 'active']);
        $district = Geography::create(['parent_id' => $province->id, 'name' => 'Lahore District', 'slug' => 'lahore', 'type' => 'district', 'status' => 'active']);
        $city = Geography::create(['parent_id' => $district->id, 'name' => 'Lahore', 'slug' => 'lahore', 'type' => 'city', 'status' => 'active']);
        $institution = Institution::create(['name' => 'Example University', 'slug' => 'example-university', 'status' => 'active']);
        $campus = Campus::create(['institution_id' => $institution->id, 'geography_id' => $city->id, 'name' => 'Main Campus', 'slug' => 'main-campus', 'status' => 'active']);

        $this->assertSame('Punjab · Lahore District · Lahore', (new InstituteGeographyContextService)->forCampus($campus));
    }

    public function test_inactive_ancestor_does_not_produce_public_context(): void
    {
        $province = Geography::create(['name' => 'Punjab', 'slug' => 'punjab', 'type' => 'province', 'status' => 'active']);
        $district = Geography::create(['parent_id' => $province->id, 'name' => 'Lahore District', 'slug' => 'lahore', 'type' => 'district', 'status' => 'inactive']);
        $city = Geography::create(['parent_id' => $district->id, 'name' => 'Lahore', 'slug' => 'lahore', 'type' => 'city', 'status' => 'active']);
        $institution = Institution::create(['name' => 'Example University', 'slug' => 'example-university', 'status' => 'active']);
        $campus = Campus::create(['institution_id' => $institution->id, 'geography_id' => $city->id, 'name' => 'Main Campus', 'slug' => 'main-campus', 'status' => 'active']);

        $this->assertNull((new InstituteGeographyContextService)->forCampus($campus));
    }

    public function test_cycle_is_rejected_without_mutation(): void
    {
        $first = new Geography(['name' => 'First', 'slug' => 'first', 'type' => 'city', 'status' => 'active']);
        $second = new Geography(['name' => 'Second', 'slug' => 'second', 'type' => 'district', 'status' => 'active']);
        $first->id = 1;
        $second->id = 2;
        $first->parent_id = 2;
        $second->parent_id = 1;
        $institution = Institution::create(['name' => 'Example University', 'slug' => 'example-university', 'status' => 'active']);
        $campus = new Campus(['institution_id' => $institution->id, 'name' => 'Main Campus', 'slug' => 'main-campus', 'status' => 'active']);
        $campus->geography = $first;

        $this->assertNull((new InstituteGeographyContextService)->forCampus($campus));
        $this->assertSame(2, $first->parent_id);
        $this->assertSame(1, $second->parent_id);
    }

    public function test_inactive_campus_does_not_produce_context(): void
    {
        $geography = Geography::create(['name' => 'Lahore', 'slug' => 'lahore', 'type' => 'city', 'status' => 'active']);
        $campus = new Campus(['geography_id' => $geography->id, 'name' => 'Archived Campus', 'slug' => 'archived-campus', 'status' => 'inactive']);

        $this->assertNull((new InstituteGeographyContextService)->forCampus($campus));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteNearbySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_nearby_api_returns_active_institutions_by_distance(): void
    {
        $city = Geography::create([
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'city',
            'status' => 'active',
        ]);

        $near = Institution::create([
            'name' => 'Near University',
            'slug' => 'near-university',
            'status' => 'active',
        ]);

        $far = Institution::create([
            'name' => 'Far University',
            'slug' => 'far-university',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $near->id,
            'geography_id' => $city->id,
            'name' => 'Near Campus',
            'slug' => 'near-campus',
            'campus_type' => 'main',
            'status' => 'active',
            'latitude' => 31.5204,
            'longitude' => 74.3587,
        ]);

        Campus::create([
            'institution_id' => $far->id,
            'geography_id' => $city->id,
            'name' => 'Far Campus',
            'slug' => 'far-campus',
            'campus_type' => 'main',
            'status' => 'active',
            'latitude' => 31.6000,
            'longitude' => 74.4500,
        ]);

        $this->getJson('/api/v1/institutions/nearby?lat=31.5204&lng=74.3587&radius_km=20')
            ->assertOk()
            ->assertJsonPath('data.0.institution.slug', 'near-university')
            ->assertJsonPath('data.0.campus.slug', 'near-campus')
            ->assertJsonPath('data.0.distance_km', 0);
    }

    public function test_nearby_api_excludes_inactive_institutions_campuses_and_geography(): void
    {
        $activeCity = Geography::create([
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'city',
            'status' => 'active',
        ]);

        $inactiveCity = Geography::create([
            'name' => 'Inactive Lahore',
            'slug' => 'inactive-lahore',
            'type' => 'city',
            'status' => 'inactive',
        ]);

        $activeInstitution = Institution::create([
            'name' => 'Active University',
            'slug' => 'active-university',
            'status' => 'active',
        ]);

        $inactiveInstitution = Institution::create([
            'name' => 'Closed University',
            'slug' => 'closed-university',
            'status' => 'closed',
        ]);

        Campus::create([
            'institution_id' => $activeInstitution->id,
            'geography_id' => $inactiveCity->id,
            'name' => 'Inactive Geography Campus',
            'slug' => 'inactive-geography-campus',
            'campus_type' => 'branch',
            'status' => 'active',
            'latitude' => 31.5204,
            'longitude' => 74.3587,
        ]);

        Campus::create([
            'institution_id' => $activeInstitution->id,
            'geography_id' => $activeCity->id,
            'name' => 'Closed Campus',
            'slug' => 'closed-campus',
            'campus_type' => 'branch',
            'status' => 'closed',
            'latitude' => 31.5204,
            'longitude' => 74.3587,
        ]);

        Campus::create([
            'institution_id' => $inactiveInstitution->id,
            'geography_id' => $activeCity->id,
            'name' => 'Closed University Campus',
            'slug' => 'closed-university-campus',
            'campus_type' => 'main',
            'status' => 'active',
            'latitude' => 31.5204,
            'longitude' => 74.3587,
        ]);

        $this->getJson('/api/v1/institutions/nearby?lat=31.5204&lng=74.3587&radius_km=5')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_nearby_api_rejects_invalid_coordinates_and_radius(): void
    {
        $this->getJson('/api/v1/institutions/nearby?lat=91&lng=74&radius_km=5')
            ->assertStatus(422);

        $this->getJson('/api/v1/institutions/nearby?lat=31&lng=181&radius_km=5')
            ->assertStatus(422);

        $this->getJson('/api/v1/institutions/nearby?lat=31&lng=74&radius_km=101')
            ->assertStatus(422);
    }

    public function test_nearby_api_deduplicates_institutions_by_closest_campus(): void
    {
        $city = Geography::create([
            'name' => 'Lahore',
            'slug' => 'lahore',
            'type' => 'city',
            'status' => 'active',
        ]);

        $institution = Institution::create([
            'name' => 'Multi Campus University',
            'slug' => 'multi-campus-university',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $city->id,
            'name' => 'Far Campus',
            'slug' => 'far-campus',
            'campus_type' => 'branch',
            'status' => 'active',
            'latitude' => 31.5500,
            'longitude' => 74.3800,
        ]);

        Campus::create([
            'institution_id' => $institution->id,
            'geography_id' => $city->id,
            'name' => 'Near Campus',
            'slug' => 'near-campus',
            'campus_type' => 'branch',
            'status' => 'active',
            'latitude' => 31.5210,
            'longitude' => 74.3590,
        ]);

        $this->getJson('/api/v1/institutions/nearby?lat=31.5204&lng=74.3587&radius_km=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.campus.slug', 'near-campus');
    }
}

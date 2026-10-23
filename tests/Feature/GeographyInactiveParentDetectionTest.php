<?php

namespace Tests\Feature;

use App\Models\Geography;
use App\Services\GeographyIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyInactiveParentDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_geography_with_inactive_parent_is_flagged(): void
    {
        $country = Geography::create([
            'name' => 'Synthetic Geography Country',
            'slug' => 'synthetic-geography-country',
            'type' => 'country',
            'status' => 'active',
        ]);
        $parent = Geography::create([
            'name' => 'Inactive Synthetic Province',
            'slug' => 'inactive-synthetic-province',
            'type' => 'province',
            'parent_id' => $country->id,
            'status' => 'inactive',
        ]);
        Geography::create([
            'name' => 'Active Synthetic District',
            'slug' => 'active-synthetic-district',
            'type' => 'district',
            'parent_id' => $parent->id,
            'status' => 'active',
        ]);

        $result = app(GeographyIntegrityService::class)->validate();

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('parent_not_active:', implode('|', $result['errors']));
    }
}

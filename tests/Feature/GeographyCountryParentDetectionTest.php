<?php

namespace Tests\Feature;

use App\Models\Geography;
use App\Services\GeographyIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyCountryParentDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_with_parent_is_flagged(): void
    {
        $parent = Geography::create([
            'name' => 'Synthetic Parent Geography',
            'slug' => 'synthetic-parent-geography',
            'type' => 'country',
            'status' => 'active',
        ]);
        Geography::create([
            'name' => 'Synthetic Nested Country',
            'slug' => 'synthetic-nested-country',
            'type' => 'country',
            'parent_id' => $parent->id,
            'status' => 'active',
        ]);

        $result = app(GeographyIntegrityService::class)->validate();

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('country_has_parent:', implode('|', $result['errors']));
    }
}

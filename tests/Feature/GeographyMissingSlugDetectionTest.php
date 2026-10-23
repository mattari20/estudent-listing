<?php

namespace Tests\Feature;

use App\Models\Geography;
use App\Services\GeographyIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyMissingSlugDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_geography_with_blank_slug_is_flagged(): void
    {
        $country = Geography::create([
            'name' => 'Synthetic Slug Test Country',
            'slug' => 'synthetic-slug-test-country',
            'type' => 'country',
            'status' => 'active',
        ]);
        Geography::create([
            'name' => 'Slugless Synthetic District',
            'slug' => ' ',
            'type' => 'district',
            'parent_id' => $country->id,
            'status' => 'active',
        ]);

        $result = app(GeographyIntegrityService::class)->validate();

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('missing_slug:', implode('|', $result['errors']));
    }
}

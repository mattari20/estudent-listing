<?php

namespace Tests\Feature;

use App\Models\Geography;
use App\Services\GeographyIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyInvalidSlugDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_geography_with_non_url_safe_slug_is_flagged(): void
    {
        Geography::create([
            'name' => 'Synthetic District',
            'slug' => 'Synthetic District!',
            'type' => 'district',
            'parent_id' => null,
            'status' => 'active',
        ]);

        $result = app(GeographyIntegrityService::class)->validate();

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('invalid_slug:', implode('|', $result['errors']));
    }
}

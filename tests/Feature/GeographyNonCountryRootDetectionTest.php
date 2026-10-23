<?php

namespace Tests\Feature;

use App\Models\Geography;
use App\Services\GeographyIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyNonCountryRootDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_country_geography_without_parent_is_flagged(): void
    {
        Geography::create([
            'name' => 'Orphan Synthetic District',
            'slug' => 'orphan-synthetic-district',
            'type' => 'district',
            'parent_id' => null,
            'status' => 'active',
        ]);

        $result = app(GeographyIntegrityService::class)->validate();

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('non_country_without_parent:', implode('|', $result['errors']));
    }
}

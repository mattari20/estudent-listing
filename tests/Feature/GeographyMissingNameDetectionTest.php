<?php

namespace Tests\Feature;

use App\Models\Geography;
use App\Services\GeographyIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyMissingNameDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_geography_with_blank_name_is_flagged(): void
    {
        $country = Geography::create([
            'name' => 'Synthetic Name Test Country',
            'slug' => 'synthetic-name-test-country',
            'type' => 'country',
            'status' => 'active',
        ]);
        Geography::create([
            'name' => '   ',
            'slug' => 'blank-name-district',
            'type' => 'district',
            'parent_id' => $country->id,
            'status' => 'active',
        ]);

        $result = app(GeographyIntegrityService::class)->validate();

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('missing_name:', implode('|', $result['errors']));
    }
}

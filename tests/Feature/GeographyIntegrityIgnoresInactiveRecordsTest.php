<?php

namespace Tests\Feature;

use App\Models\Geography;
use App\Services\GeographyIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyIntegrityIgnoresInactiveRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_geography_is_not_reported_as_an_active_integrity_finding(): void
    {
        Geography::create([
            'name' => '',
            'slug' => 'INVALID SLUG!',
            'type' => 'district',
            'parent_id' => null,
            'status' => 'inactive',
        ]);

        $result = app(GeographyIntegrityService::class)->validate();

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['errors']);
    }
}

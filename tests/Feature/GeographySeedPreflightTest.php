<?php

namespace Tests\Feature;

use App\Models\Geography;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographySeedPreflightTest extends TestCase
{
    use RefreshDatabase;

    public function test_preflight_reports_expected_records_without_seeding_them(): void
    {
        $this->artisan('institutes:geography-seed-preflight')
            ->assertExitCode(0)
            ->expectsOutputToContain('country:pakistan')
            ->expectsOutputToContain('province:punjab')
            ->expectsOutputToContain('Read-only preflight; mutation performed: no')
            ->expectsOutputToContain('Create: 8; update: 0; unchanged: 0');

        $this->assertSame(0, Geography::query()->count());
    }

    public function test_preflight_detects_existing_record_changes_without_applying_them(): void
    {
        Geography::query()->create([
            'parent_id' => null,
            'name' => 'Pakistan - operator value',
            'slug' => 'pakistan',
            'type' => 'country',
            'code' => 'PK',
            'status' => 'active',
        ]);

        $this->artisan('institutes:geography-seed-preflight')
            ->assertExitCode(1)
            ->expectsOutputToContain('country:pakistan')
            ->expectsOutputToContain('update_existing')
            ->expectsOutputToContain('Approval required: yes');

        $this->assertSame('Pakistan - operator value', Geography::query()->where('slug', 'pakistan')->value('name'));
        $this->assertSame(1, Geography::query()->count());
    }
}

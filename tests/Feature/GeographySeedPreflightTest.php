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
            ->expectsOutputToContain('Create: 8; update: 0; unchanged: 0; duplicate identities: 0; conflicts: 0');

        $this->assertSame(0, Geography::query()->count());
    }

    public function test_preflight_fails_closed_when_duplicate_seed_identities_exist(): void
    {
        Geography::query()->create([
            'parent_id' => null,
            'name' => 'Pakistan One',
            'slug' => 'pakistan',
            'type' => 'country',
            'code' => 'PK',
            'status' => 'active',
        ]);
        Geography::query()->create([
            'parent_id' => null,
            'name' => 'Pakistan Two',
            'slug' => 'pakistan',
            'type' => 'country',
            'code' => 'PK',
            'status' => 'active',
        ]);

        $this->artisan('institutes:geography-seed-preflight')
            ->assertExitCode(1)
            ->expectsOutputToContain('Overall status: critical')
            ->expectsOutputToContain('Create: 7; update: 0; unchanged: 0; duplicate identities: 1; conflicts: 1');

        $this->assertSame(2, Geography::query()->where('slug', 'pakistan')->count());
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
            ->expectsOutputToContain('Overall status: critical')
            ->expectsOutputToContain('Approval required: yes');

        $this->assertSame('Pakistan - operator value', Geography::query()->where('slug', 'pakistan')->value('name'));
        $this->assertSame(1, Geography::query()->count());
    }

    public function test_seed_creates_missing_records_and_reports_created_count(): void
    {
        $this->artisan('institutes:seed-pakistan-geography')
            ->assertExitCode(0)
            ->expectsOutputToContain('Created 8 Pakistan geography records; existing records were not modified.');

        $this->assertSame(8, Geography::query()->count());
        $country = Geography::query()->where('slug', 'pakistan')->firstOrFail();
        $this->assertSame('country', $country->type);
        $this->assertSame(7, Geography::query()->where('parent_id', $country->id)->count());
    }

    public function test_seed_does_not_modify_existing_records_when_values_differ(): void
    {
        Geography::query()->create([
            'parent_id' => null,
            'name' => 'Pakistan - operator value',
            'slug' => 'pakistan',
            'type' => 'country',
            'code' => 'PK',
            'status' => 'inactive',
        ]);

        $this->artisan('institutes:seed-pakistan-geography')
            ->assertExitCode(1)
            ->expectsOutputToContain('existing country:pakistan differs')
            ;

        $this->assertSame('Pakistan - operator value', Geography::query()->where('slug', 'pakistan')->value('name'));
        $this->assertSame('inactive', Geography::query()->where('slug', 'pakistan')->value('status'));
        $this->assertSame(1, Geography::query()->count());
    }

    public function test_seed_stops_on_duplicate_identity_without_modifying_records(): void
    {
        foreach (['Pakistan One', 'Pakistan Two'] as $name) {
            Geography::query()->create([
                'parent_id' => null,
                'name' => $name,
                'slug' => 'pakistan',
                'type' => 'country',
                'code' => 'PK',
                'status' => 'active',
            ]);
        }

        $this->artisan('institutes:seed-pakistan-geography')
            ->assertExitCode(1)
            ->expectsOutputToContain('duplicate identity country:pakistan');

        $this->assertSame(2, Geography::query()->where('slug', 'pakistan')->count());
        $this->assertSame(['Pakistan One', 'Pakistan Two'], Geography::query()->where('slug', 'pakistan')->orderBy('id')->pluck('name')->all());
    }

    public function test_seed_stops_when_existing_child_has_no_country_without_modifying_it(): void
    {
        Geography::query()->create([
            'parent_id' => null,
            'name' => 'Punjab',
            'slug' => 'punjab',
            'type' => 'province',
            'code' => 'PK-PB',
            'status' => 'active',
        ]);

        $this->artisan('institutes:seed-pakistan-geography')
            ->assertExitCode(1)
            ->expectsOutputToContain('existing province:punjab has no canonical Pakistan country');

        $this->assertSame(1, Geography::query()->count());
        $this->assertSame(null, Geography::query()->where('slug', 'punjab')->value('parent_id'));
    }
}

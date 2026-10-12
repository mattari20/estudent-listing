<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_health_command_reports_json_status(): void
    {
        $this->artisan('institutes:database-health', ['--json' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('institutes_database_health');
    }

    public function test_database_health_command_reports_required_tables_geography_population_and_read_only_mode(): void
    {
        $this->artisan('institutes:database-health')
            ->assertExitCode(0)
            ->expectsOutputToContain('required_tables')
            ->expectsOutputToContain('geography_reference_data')
            ->expectsOutputToContain('Read-only check; mutation performed: no');
    }
}

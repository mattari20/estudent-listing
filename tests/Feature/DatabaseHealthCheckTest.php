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


    public function test_database_health_json_contract_is_healthy_and_read_only(): void
    {
        \\Illuminate\\Support\\Facades\\Artisan::call('institutes:database-health', ['--json' => true]);
        $report = json_decode(\\Illuminate\\Support\\Facades\\Artisan::output(), true);

        $this->assertIsArray($report);
        $this->assertSame('institutes_database_health', $report['check'] ?? null);
        $this->assertSame('healthy', $report['status'] ?? null);
        $this->assertFalse($report['mutation_performed'] ?? true);
    }
}

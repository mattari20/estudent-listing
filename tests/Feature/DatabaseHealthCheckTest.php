<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_health_command_reports_read_only_status(): void
    {
        $this->artisan('institutes:database-health', ['--json' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('institutes_database_health')
            ->expectsOutputToContain('mutation_performed')
            ->expectsOutputToContain('false');
    }

    public function test_database_health_command_reports_required_tables(): void
    {
        $this->artisan('institutes:database-health', ['--json' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('required_tables')
            ->expectsOutputToContain('missing')
            ->expectsOutputToContain('[]');
    }
}

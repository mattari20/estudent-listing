<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AutomatedDiagnosticsEndpointTest extends TestCase
{
    public function test_endpoint_is_hidden_until_a_strong_secret_is_configured(): void
    {
        config(['services.institutes_diagnostics.token' => '']);
        $this->getJson('/institutes/automation/diagnostics')->assertNotFound();
    }

    public function test_endpoint_rejects_requests_without_the_bearer_token(): void
    {
        config(['services.institutes_diagnostics.token' => str_repeat('a', 64)]);
        $this->getJson('/institutes/automation/diagnostics')->assertForbidden();
    }

    public function test_endpoint_rejects_an_incorrect_bearer_token(): void
    {
        config(['services.institutes_diagnostics.token' => str_repeat('a', 64)]);
        $this->withHeader('Authorization', 'Bearer '.str_repeat('b', 64))
            ->getJson('/institutes/automation/diagnostics')->assertForbidden();
    }

    public function test_endpoint_returns_all_three_read_only_reports_without_caching(): void
    {
        config(['services.institutes_diagnostics.token' => str_repeat('a', 64)]);

        $aggregate = ['check' => 'institutes_data_gap_report', 'status' => 'warning', 'mutation_performed' => false];
        $audit = ['check' => 'institutes_data_gap_record_audit', 'status' => 'complete', 'mutation_performed' => false];
        $geography = ['check' => 'institutes_geography_seed_preflight', 'status' => 'warning', 'mutation_performed' => false, 'seed_command_executed' => false];

        Artisan::shouldReceive('call')->once()->with('institutes:data-gap-report', ['--json' => true])->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('institutes:data-gap-record-audit', ['--json' => true])->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('institutes:geography-seed-preflight', ['--json' => true])->andReturn(0);
        Artisan::shouldReceive('output')->times(3)->andReturn(json_encode($aggregate), json_encode($audit), json_encode($geography));

        $response = $this->withHeader('Authorization', 'Bearer '.str_repeat('a', 64))
            ->getJson('/institutes/automation/diagnostics')
            ->assertOk()
            ->assertJsonPath('reports.report.data.check', 'institutes_data_gap_report')
            ->assertJsonPath('reports.record_audit.data.check', 'institutes_data_gap_record_audit')
            ->assertJsonPath('reports.geography_preflight.data.check', 'institutes_geography_seed_preflight')
            ->assertJsonPath('reports.geography_preflight.data.seed_command_executed', false)
            ->assertJsonPath('mutation_performed', false);

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_endpoint_fails_closed_if_a_report_claims_a_mutation(): void
    {
        config(['services.institutes_diagnostics.token' => str_repeat('a', 64)]);
        Artisan::shouldReceive('call')->once()->with('institutes:data-gap-report', ['--json' => true])->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn(json_encode([
            'check' => 'institutes_data_gap_report', 'status' => 'warning', 'mutation_performed' => true,
        ]));

        $this->withHeader('Authorization', 'Bearer '.str_repeat('a', 64))
            ->getJson('/institutes/automation/diagnostics')
            ->assertStatus(500)
            ->assertJsonPath('mutation_performed', false);
    }

    public function test_diagnostics_endpoint_remains_hidden_without_configured_secret(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        config(['services.institutes_diagnostics.token' => '']);
        $this->getJson('/institutes/automation/diagnostics')->assertNotFound();
    }
}

<?php

namespace Tests\\Feature;

use Illuminate\\Support\\Facades\\Artisan;
use Tests\\TestCase;

class AutomatedDiagnosticsEndpointTest extends TestCase
{
    public function test_endpoint_is_hidden_until_a_strong_secret_is_configured(): void
    {
        config(['services.institutes_diagnostics.token' => '']);

        $this->getJson('/institutes/automation/diagnostics')
            ->assertNotFound();
    }

    public function test_endpoint_rejects_requests_without_the_bearer_token(): void
    {
        config(['services.institutes_diagnostics.token' => str_repeat('a', 64)]);

        $this->getJson('/institutes/automation/diagnostics')
            ->assertForbidden();
    }

    public function test_endpoint_rejects_an_incorrect_bearer_token(): void
    {
        config(['services.institutes_diagnostics.token' => str_repeat('a', 64)]);

        $this->withHeader('Authorization', 'Bearer '.str_repeat('b', 64))
            ->getJson('/institutes/automation/diagnostics')
            ->assertForbidden();
    }

    public function test_endpoint_returns_both_read_only_reports_without_caching(): void
    {
        config(['services.institutes_diagnostics.token' => str_repeat('a', 64)]);

        $aggregate = [
            'check' => 'institutes_data_gap_report',
            'status' => 'warning',
            'mutation_performed' => false,
        ];
        $recordAudit = [
            'check' => 'institutes_data_gap_record_audit',
            'status' => 'complete',
            'mutation_performed' => false,
        ];

        Artisan::shouldReceive('call')
            ->once()
            ->with('institutes:data-gap-report', ['--json' => true])
            ->andReturn(0);
        Artisan::shouldReceive('call')
            ->once()
            ->with('institutes:data-gap-record-audit', ['--json' => true])
            ->andReturn(0);
        Artisan::shouldReceive('output')
            ->twice()
            ->andReturn(json_encode($aggregate), json_encode($recordAudit));

        $this->withHeader('Authorization', 'Bearer '.str_repeat('a', 64))
            ->getJson('/institutes/automation/diagnostics')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store')
            ->assertJsonPath('report.check', 'institutes_data_gap_report')
            ->assertJsonPath('record_audit.check', 'institutes_data_gap_record_audit')
            ->assertJsonPath('report.mutation_performed', false)
            ->assertJsonPath('record_audit.mutation_performed', false);
    }
}

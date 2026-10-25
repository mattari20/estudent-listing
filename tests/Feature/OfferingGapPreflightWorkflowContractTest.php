<?php

namespace Tests\Feature;

use Tests\TestCase;

class OfferingGapPreflightWorkflowContractTest extends TestCase
{
    public function test_live_preflight_workflow_remains_manual_exact_revision_and_read_only(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/offering-gap-preflight.yml'));

        $this->assertIsString($workflow);
        $this->assertStringContainsString('workflow_dispatch:', $workflow);
        $this->assertStringContainsString('type: string', $workflow);
        $this->assertStringContainsString('REQUESTED_REVISION', $workflow);
        $this->assertStringContainsString('[0-9a-fA-F]{40}', $workflow);
        $this->assertStringContainsString('environment: offering-gap-readonly-audit', $workflow);
        $this->assertStringContainsString('persist-credentials: false', $workflow);
        $this->assertStringContainsString('production_write_authorized: false', $workflow);
        $this->assertStringContainsString('database_mutations', $workflow);
        $this->assertStringContainsString('mutation_performed', $workflow);
        $this->assertStringContainsString('retention-days: 3', $workflow);
        $this->assertStringContainsString('institutes:offering-gap-preflight --json --no-ansi', $workflow);
        $this->assertStringNotContainsString('php artisan migrate', $workflow);
        $this->assertStringNotContainsString('php artisan db:seed', $workflow);
    }

    public function test_live_preflight_workflow_requires_exactly_36_unique_program_rows(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/offering-gap-preflight.yml'));

        $this->assertIsString($workflow);
        $this->assertStringContainsString('.summary.register_rows == 36', $workflow);
        $this->assertStringContainsString('(.records | length) == 36', $workflow);
        $this->assertStringContainsString('unique | length', $workflow);
        $this->assertStringContainsString('command_exit_code', $workflow);
        $this->assertStringContainsString('preflight_status', $workflow);
    }
}

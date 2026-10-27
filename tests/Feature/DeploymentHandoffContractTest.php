<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeploymentHandoffContractTest extends TestCase
{
    public function test_governed_discovery_requires_confirmed_schema_handoff_bound_to_run(): void
    {
        $deployWorkflow = file_get_contents(base_path('.github/workflows/controlled-hostinger-code-deploy.yml'));
        $migrationWorkflow = file_get_contents(base_path('.github/workflows/controlled-official-source-refresh-migration.yml'));
        $discoveryWorkflow = file_get_contents(base_path('.github/workflows/scheduled-institute-discovery.yml'));
        $readinessWorkflow = file_get_contents(base_path('.github/workflows/official-source-refresh-readiness.yml'));

        $this->assertIsString($deployWorkflow);
        $this->assertIsString($migrationWorkflow);
        $this->assertIsString($discoveryWorkflow);
        $this->assertIsString($readinessWorkflow);

        // Deployment reports success only after independent application-path parity
        // verification and the read-only Laravel smoke/offering preflight.
        $this->assertStringContainsString('application_code_parity=VERIFIED', $deployWorkflow);
        $this->assertStringContainsString('application_code_parity_method=git_diff_target_vs_deployed_paths', $deployWorkflow);
        $this->assertStringContainsString('read_only_preflight=COMPLETED', $deployWorkflow);
        $this->assertStringContainsString('target_revision=$TARGET_REVISION', $deployWorkflow);
        $this->assertStringContainsString('deployment_run_id=$GITHUB_RUN_ID', $deployWorkflow);
        $this->assertStringContainsString('workflow_run_head_sha=$GITHUB_SHA', $deployWorkflow);
        $this->assertStringContainsString('workflow_run_name=Controlled Hostinger Code Deployment', $deployWorkflow);
        $this->assertStringContainsString('deployment_attempt=$GITHUB_RUN_ATTEMPT', $deployWorkflow);

        // Production migration is manual/confirmed only. It emits the discovery handoff
        // only when the SSH-side schema/ledger checks complete successfully.
        $this->assertStringContainsString('confirm_migration', $migrationWorkflow);
        $this->assertStringContainsString('migration_status', $migrationWorkflow);
        $this->assertStringContainsString('missing_indexes', $migrationWorkflow);
        $this->assertStringContainsString('Prepare verified source-refresh schema handoff', $migrationWorkflow);
        $this->assertStringContainsString('schema_ready=true', $migrationWorkflow);
        $this->assertStringContainsString('migration_confirmation=true', $migrationWorkflow);
        $this->assertStringContainsString('migration_run_id=$GITHUB_RUN_ID', $migrationWorkflow);
        $this->assertStringContainsString('workflow_run_name=Controlled Official-Source Refresh Migration', $migrationWorkflow);
        $this->assertStringNotContainsString("\n  push:\n", $migrationWorkflow);

        // Discovery consumes a schema handoff from the exact triggering migration run,
        // verifies its identity and application target, and rechecks schema readiness
        // on Hostinger before any source retrieval or import staging.
        $this->assertStringContainsString('official-source-refresh-verified-handoff', $discoveryWorkflow);
        $this->assertStringContainsString('schema_ready=true', $discoveryWorkflow);
        $this->assertStringContainsString('migration_confirmation=true', $discoveryWorkflow);
        $this->assertStringContainsString('migration_run_id=$EXPECTED_MIGRATION_RUN_ID', $discoveryWorkflow);
        $this->assertStringContainsString('workflow_run_head_sha=$EXPECTED_WORKFLOW_HEAD_SHA', $discoveryWorkflow);
        $this->assertStringContainsString('workflow_run_name=Controlled Official-Source Refresh Migration', $discoveryWorkflow);
        $this->assertStringContainsString('SCHEMA_HANDOFF_TARGET_REVISION', $discoveryWorkflow);
        $this->assertStringContainsString('official_source_refresh_schema_gate', $discoveryWorkflow);
        $this->assertStringContainsString('BLOCKED_OFFICIAL_SOURCE_REFRESH_SCHEMA_NOT_READY', $discoveryWorkflow);
        $this->assertStringContainsString('workflow_run:', $discoveryWorkflow);
        $this->assertStringContainsString('- "Controlled Official-Source Refresh Migration"', $discoveryWorkflow);
        $this->assertStringContainsString('schedule:', $discoveryWorkflow);
        $this->assertStringNotContainsString("\n  push:\n", $discoveryWorkflow);

        // Readiness repeats after the controlled migration workflow succeeds.
        $this->assertStringContainsString('workflow_run:', $readinessWorkflow);
        $this->assertStringContainsString('- Controlled Official-Source Refresh Migration', $readinessWorkflow);
        $this->assertStringContainsString('schema_ready', $readinessWorkflow);
    }
}

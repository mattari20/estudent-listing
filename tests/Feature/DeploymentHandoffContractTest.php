<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeploymentHandoffContractTest extends TestCase
{
    public function test_discovery_requires_the_exact_verified_deployment_handoff(): void
    {
        $deployWorkflow = file_get_contents(base_path('.github/workflows/controlled-hostinger-code-deploy.yml'));
        $discoveryWorkflow = file_get_contents(base_path('.github/workflows/scheduled-institute-discovery.yml'));

        $this->assertIsString($deployWorkflow);
        $this->assertIsString($discoveryWorkflow);
        $this->assertStringContainsString('application_code_parity=VERIFIED', $deployWorkflow);
        $this->assertStringContainsString('application_code_parity_method=git_diff_target_vs_deployed_paths', $deployWorkflow);
        $this->assertStringContainsString('read_only_preflight=COMPLETED', $deployWorkflow);
        $this->assertStringContainsString('target_revision=$TARGET_REVISION', $deployWorkflow);
        $this->assertStringContainsString('deployment_run_id=$GITHUB_RUN_ID', $deployWorkflow);
        $this->assertStringContainsString('workflow_run_head_sha=$GITHUB_SHA', $deployWorkflow);
        $this->assertStringContainsString('workflow_run_name=Controlled Hostinger Code Deployment', $deployWorkflow);
        $this->assertStringContainsString('deployment_attempt=$GITHUB_RUN_ATTEMPT', $deployWorkflow);

        $this->assertStringContainsString('application_code_parity=VERIFIED', $discoveryWorkflow);
        $this->assertStringContainsString('application_code_parity_method=git_diff_target_vs_deployed_paths', $discoveryWorkflow);
        $this->assertStringContainsString('deployment_run_id=$EXPECTED_DEPLOYMENT_RUN_ID', $discoveryWorkflow);
        $this->assertStringContainsString('workflow_run_head_sha=$EXPECTED_WORKFLOW_HEAD_SHA', $discoveryWorkflow);
        $this->assertStringContainsString('workflow_run_name=Controlled Hostinger Code Deployment', $discoveryWorkflow);
        $this->assertStringContainsString('DEPLOYMENT_HANDOFF_TARGET_REVISION', $discoveryWorkflow);
        $this->assertStringContainsString('Verified deployment target does not match current main application revision.', $discoveryWorkflow);
    }
}

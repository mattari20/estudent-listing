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
        $migrationDiagnostic = file_get_contents(base_path('scripts/ops/official-source-refresh-diagnostic.php'));
        $migrationConfig = $this->parseWorkflowYaml(base_path('.github/workflows/controlled-official-source-refresh-migration.yml'));
        $discoveryConfig = $this->parseWorkflowYaml(base_path('.github/workflows/scheduled-institute-discovery.yml'));
        $readinessConfig = $this->parseWorkflowYaml(base_path('.github/workflows/official-source-refresh-readiness.yml'));

        $this->assertIsString($deployWorkflow);
        $this->assertIsString($migrationWorkflow);
        $this->assertIsString($discoveryWorkflow);
        $this->assertIsString($readinessWorkflow);
        $this->assertIsString($migrationDiagnostic);

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
        $this->assertStringContainsString('migration_status', $migrationDiagnostic);
        $this->assertStringContainsString('missing_indexes', $migrationDiagnostic);
        $this->assertStringContainsString('scripts/ops/official-source-refresh-diagnostic.php', $migrationWorkflow);
        $this->assertStringContainsString('Prepare verified source-refresh schema handoff', $migrationWorkflow);
        $this->assertStringContainsString('schema_ready=true', $migrationWorkflow);
        $this->assertStringContainsString('migration_confirmation=true', $migrationWorkflow);
        $this->assertStringContainsString('migration_run_id=$GITHUB_RUN_ID', $migrationWorkflow);
        $this->assertStringContainsString('workflow_run_name=Controlled Official-Source Refresh Migration', $migrationWorkflow);
        $migrationEvents = $migrationConfig['on'] ?? [];
        $this->assertArrayHasKey('workflow_dispatch', $migrationEvents);
        $this->assertArrayNotHasKey('push', $migrationEvents);
        $this->assertArrayNotHasKey('pull_request', $migrationEvents);
        $this->assertArrayNotHasKey('schedule', $migrationEvents);
        $this->assertArrayNotHasKey('workflow_run', $migrationEvents);
        $this->assertArrayHasKey('target_sha', $migrationEvents['workflow_dispatch']['inputs'] ?? []);
        $this->assertArrayHasKey('operation_reason', $migrationEvents['workflow_dispatch']['inputs'] ?? []);
        $this->assertArrayHasKey('confirm_production_operation', $migrationEvents['workflow_dispatch']['inputs'] ?? []);
        $this->assertProtectedProductionJobs($migrationConfig, 'production-operations');

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
        $this->assertStringContainsString('workflow_dispatch:', $discoveryWorkflow);
        $this->assertStringContainsString('target_sha:', $discoveryWorkflow);
        $this->assertStringContainsString('environment: production-operations', $discoveryWorkflow);
        $this->assertStringContainsString('scripts/ci/validate-production-dispatch.sh', $discoveryWorkflow);
        $discoveryEvents = $discoveryConfig['on'] ?? [];
        $this->assertArrayHasKey('workflow_dispatch', $discoveryEvents);
        $this->assertArrayNotHasKey('workflow_run', $discoveryEvents);
        $this->assertArrayNotHasKey('schedule', $discoveryEvents);
        $this->assertArrayNotHasKey('push', $discoveryEvents);
        $this->assertArrayNotHasKey('pull_request', $discoveryEvents);
        $this->assertArrayHasKey('target_sha', $discoveryEvents['workflow_dispatch']['inputs'] ?? []);
        $this->assertArrayHasKey('operation_reason', $discoveryEvents['workflow_dispatch']['inputs'] ?? []);
        $this->assertArrayHasKey('confirm_production_operation', $discoveryEvents['workflow_dispatch']['inputs'] ?? []);
        $this->assertProtectedProductionJobs($discoveryConfig, 'production-operations');

        // Readiness is now an explicit, protected manual dispatch; no workflow_run
        // handoff can expose production credentials or bypass the authorization gate.
        $this->assertStringContainsString('workflow_dispatch:', $readinessWorkflow);
        $this->assertStringContainsString('target_sha:', $readinessWorkflow);
        $this->assertStringContainsString('environment: production-readonly-diagnostics', $readinessWorkflow);
        $this->assertStringContainsString('scripts/ci/validate-production-dispatch.sh', $readinessWorkflow);
        $readinessEvents = $readinessConfig['on'] ?? [];
        $this->assertArrayHasKey('workflow_dispatch', $readinessEvents);
        $this->assertArrayNotHasKey('workflow_run', $readinessEvents);
        $this->assertArrayNotHasKey('schedule', $readinessEvents);
        $this->assertArrayNotHasKey('push', $readinessEvents);
        $this->assertArrayNotHasKey('pull_request', $readinessEvents);
        $this->assertArrayHasKey('target_sha', $readinessEvents['workflow_dispatch']['inputs'] ?? []);
        $this->assertArrayHasKey('operation_reason', $readinessEvents['workflow_dispatch']['inputs'] ?? []);
        $this->assertArrayHasKey('confirm_production_operation', $readinessEvents['workflow_dispatch']['inputs'] ?? []);
        $this->assertProtectedProductionJobs($readinessConfig, 'production-readonly-diagnostics');
    }
    /**
     * Parse workflow YAML structurally so comments and embedded shell text are
     * not confused with top-level GitHub Actions event triggers.
     */
    private function parseWorkflowYaml(string $path): array
    {
        $process = proc_open(
            ['ruby', base_path('scripts/ci/read-workflow-yaml.rb'), $path],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $status = proc_close($process);
        $this->assertSame(0, $status, $error);

        return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    }

    private function assertProtectedProductionJobs(array $workflow, string $environment): void
    {
        $productionJobs = [];
        foreach (($workflow['jobs'] ?? []) as $name => $job) {
            if ($this->jobHasProductionAccess($job)) {
                $productionJobs[$name] = $job;
            }
        }

        $this->assertNotEmpty($productionJobs, 'Expected at least one production-access job.');

        foreach ($productionJobs as $name => $job) {
            $this->assertFalse($this->containsProductionSecret($job['env'] ?? []), $name.' must not expose production secrets at job scope.');
            $this->assertSame($environment, $job['environment'] ?? null, $name.' environment mismatch.');
            $steps = $job['steps'] ?? [];
            $accessIndex = null;
            $guardIndex = null;
            foreach ($steps as $index => $step) {
                if ($guardIndex === null && str_contains((string) ($step['run'] ?? ''), 'scripts/ci/validate-production-dispatch.sh')) {
                    $guardIndex = $index;
                }
                if ($accessIndex === null && $this->stepHasProductionAccess($step)) {
                    $accessIndex = $index;
                }
            }
            $this->assertNotNull($accessIndex, $name.' production access step missing.');
            $this->assertNotNull($guardIndex, $name.' dispatch guard missing.');
            $this->assertLessThan($accessIndex, $guardIndex, $name.' guard must run before production access.');
        }
    }

    private function jobHasProductionAccess(array $job): bool
    {
        if ($this->containsProductionSecret($job['env'] ?? [])) {
            return true;
        }

        foreach (($job['steps'] ?? []) as $step) {
            if ($this->stepHasProductionAccess($step)) {
                return true;
            }
        }

        return false;
    }

    private function stepHasProductionAccess(array $step): bool
    {
        if (str_starts_with((string) ($step['uses'] ?? ''), 'appleboy/ssh-action')) {
            return true;
        }

        return $this->containsProductionSecret([
            'env' => $step['env'] ?? [],
            'with' => $step['with'] ?? [],
        ]);
    }

    private function containsProductionSecret(mixed $configuration): bool
    {
        $serialized = json_encode($configuration, JSON_THROW_ON_ERROR);

        foreach ([
            'secrets.HOSTINGER_SSH_',
            'secrets.OFFERING_GAP_DB_',
            'secrets.INSTITUTES_DIAGNOSTICS_TOKEN',
            'secrets.INSTITUTES_PRIVATE_REPORTS_TOKEN',
        ] as $marker) {
            if (str_contains($serialized, $marker)) {
                return true;
            }
        }

        return false;
    }

}

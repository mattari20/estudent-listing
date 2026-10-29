<?php

namespace Tests\Feature;

use Tests\TestCase;

class ControlledImportBatchDuplicateRowsMigrationWorkflowContractTest extends TestCase
{
    public function test_guarded_migration_runs_php_preflight_from_an_explicit_script_file(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/controlled-import-batch-duplicate-rows-migration.yml'));

        $this->assertIsString($workflow);
        $this->assertStringContainsString('PREFLIGHT_SCRIPT_FILE="$(mktemp)"', $workflow);
        $this->assertStringContainsString('php_preflight_stage=starting', $workflow);
        $this->assertStringContainsString('php_preflight_exit_code=$PREFLIGHT_PHP_EXIT', $workflow);
        $this->assertStringContainsString('php_cli_probe_stage=starting', $workflow);
        $this->assertStringContainsString('php_cli_version_exit_code=$PHP_VERSION_EXIT', $workflow);
        $this->assertStringContainsString('php_cli_probe=ok', $workflow);
        $this->assertStringContainsString('"$PHP_BIN" -d display_errors=1 -d log_errors=0 "$PREFLIGHT_SCRIPT_FILE"', $workflow);
        $this->assertStringNotContainsString('script_stop: true', $workflow);
        $this->assertStringContainsString('set -eu', $workflow);
        $this->assertStringNotContainsString('if php >"$PREFLIGHT_OUTPUT_FILE" 2>&1 <<\'PHP\'', $workflow);
    }

    public function test_guarded_migration_keeps_dry_run_default_and_single_migration_scope(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/controlled-import-batch-duplicate-rows-migration.yml'));

        $this->assertIsString($workflow);
        $this->assertStringContainsString('default: false', $workflow);
        $this->assertStringContainsString('CONFIRM_MIGRATION: ${{ inputs.confirm_migration }}', $workflow);
        $this->assertStringContainsString('if [ "$CONFIRM_MIGRATION" != "true" ]; then', $workflow);
        $this->assertStringContainsString('php artisan migrate --force --path="$MIGRATION_FILE" --no-interaction', $workflow);
        $this->assertStringNotContainsString('php artisan migrate --force --no-interaction', $workflow);
    }

    public function test_post_migration_verification_uses_explicit_script_file_and_cleans_up(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/controlled-import-batch-duplicate-rows-migration.yml'));

        $this->assertIsString($workflow);
        $this->assertStringContainsString('POSTCHECK_SCRIPT_FILE="$(mktemp)"', $workflow);
        $this->assertStringContainsString('post_migration_php_exit_code=$POSTCHECK_PHP_EXIT', $workflow);
        $this->assertStringContainsString('"$POSTCHECK_SCRIPT_FILE" >"$POSTCHECK_OUTPUT_FILE" 2>&1', $workflow);
        $this->assertStringContainsString('"$POSTCHECK_SCRIPT_FILE" "$POSTCHECK_OUTPUT_FILE"', $workflow);
    }
}

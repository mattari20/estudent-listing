<?php

namespace Tests\Feature;

use Tests\TestCase;

class HostingerDirectoryVisibilityDiagnosticsContractTest extends TestCase
{
    public function test_hostinger_diagnostics_report_canonical_counts_and_relationship_integrity_read_only(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/hostinger-readonly-diagnostics.yml'));

        $this->assertIsString($workflow);
        $this->assertStringContainsString('canonical_directory_visibility_counts', $workflow);
        $this->assertStringContainsString('candidate_import_source_evidence_lineage', $workflow);
        $this->assertStringContainsString('active_evidence', $workflow);
        $this->assertStringContainsString('identity_decisions', $workflow);
        $this->assertStringContainsString('promotion_decisions', $workflow);
        $this->assertStringContainsString('promotion_events', $workflow);
        $this->assertStringContainsString("'institutions', 'campuses', 'programs', 'program_offerings'", $workflow);
        $this->assertStringContainsString("'campuses_missing_institution'", $workflow);
        $this->assertStringContainsString("'programs_missing_institution'", $workflow);
        $this->assertStringContainsString("'offerings_missing_program'", $workflow);
        $this->assertStringContainsString("'offerings_missing_campus'", $workflow);
        $this->assertStringContainsString("'mutation_performed' => false", $workflow);
        $this->assertStringContainsString("'rows_by_status'", $workflow);
        $this->assertStringContainsString("'soft_deleted_rows'", $workflow);
        $this->assertStringContainsString('expected_hostinger_cron_command=', $workflow);
        $this->assertStringContainsString('Never fetch or update refs inside the live Hostinger repository', $workflow);
        $this->assertStringContainsString('disposable temporary repository', $workflow);
        $this->assertStringContainsString('disposable temporary repository', $workflow);
        $this->assertStringContainsString('PARITY_REPO="$(mktemp -d', $workflow);
        $this->assertStringContainsString('git -C "$PARITY_REPO" fetch --quiet --no-tags --depth=200 origin +refs/heads/main:refs/remotes/origin/main', $workflow);
        $this->assertStringContainsString('isolated_expected_tree_vs_deployed_file_content', $workflow);
        $parityStart = strpos($workflow, '=== Deployed code revision and application-path parity (isolated, read-only) ===');
        $parityEnd = strpos($workflow, '=== Migration source inventory for import_batches/duplicate_rows (read-only) ===', $parityStart === false ? 0 : $parityStart);
        $this->assertNotFalse($parityStart);
        $this->assertNotFalse($parityEnd);
        $parityBlock = substr($workflow, $parityStart, $parityEnd - $parityStart);
        $this->assertStringNotContainsString('git -C "$PROJECT_PATH" fetch', $parityBlock);
        $this->assertStringNotContainsString('git -C "$PROJECT_PATH" log', $parityBlock);
        $this->assertStringContainsString('trap \'rm -rf "$PARITY_REPO"\' EXIT', $parityBlock);
        $this->assertStringContainsString('merge-base --is-ancestor "$EXPECTED_REVISION" origin/main', $workflow);
        $this->assertStringContainsString('git -C "$PARITY_REPO" log --all --format=', $workflow);
        $this->assertStringNotContainsString('git fetch --quiet --no-tags --depth=200 origin main || DEPLOYMENT_DRIFT=1', $workflow);
        $this->assertStringContainsString('git -C "$PARITY_REPO" fetch --quiet --no-tags --depth=200 origin "$EXPECTED_REVISION"', $workflow);
        $this->assertStringContainsString('git -C "$PARITY_REPO" fetch --quiet --no-tags --depth=200 origin +refs/heads/main:refs/remotes/origin/main', $workflow);
        $this->assertStringNotContainsString('git fetch --quiet --no-tags --unshallow origin main', $workflow);
        $this->assertStringContainsString('Deployed API route registration (read-only; bounded)', $workflow);
        $this->assertStringContainsString('"$PHP_BIN" artisan route:list --json --no-ansi', $workflow);
        $this->assertStringContainsString('institution_api_route_registration', $workflow);
        $this->assertStringContainsString("PHP_BIN='/opt/alt/php85/usr/bin/php'", $workflow);
        $this->assertStringContainsString('phase_5b18_php_binary=$PHP_BIN', $workflow);
        $this->assertStringContainsString('Deployed API route registration (read-only; bounded)', $workflow);
        $this->assertStringContainsString('institution_api_route_registration', $workflow);
        $this->assertStringContainsString('"$PHP_BIN" artisan route:list --json --no-ansi', $workflow);
        $this->assertStringContainsString('artisan schedule:run', $workflow);
        $this->assertStringContainsString('git -C "$PARITY_REPO" log --all --format=\'%H %cs %s\' -S\'duplicate_rows\' -- database/migrations', $workflow);
        $this->assertStringContainsString('git -C "$PARITY_REPO" log --all --format=\'%H %cs %s\' -S\'import_batches\' -- database/migrations', $workflow);
    }


    public function test_phase_5b18_diagnostic_preserves_partial_private_report_and_lints_before_execution(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/hostinger-readonly-diagnostics.yml'));
        $diagnostic = file_get_contents(base_path('scripts/ops/phase-5b18-runtime-diagnostic.php'));

        $this->assertIsString($workflow);
        $this->assertIsString($diagnostic);
        $this->assertSame(1, substr_count($workflow, "PHP_BIN='/opt/alt/php85/usr/bin/php'"));
        $this->assertStringContainsString('"$PHP_BIN" -l "$DIAG_SCRIPT"', $workflow);
        $this->assertStringContainsString('runtime-diagnostic.stderr', $workflow);
        $this->assertStringContainsString('chmod 700 "$DIAG_ROOT"', $workflow);
        $this->assertStringContainsString('phase-5b18-artisan-runtime.json', $workflow);
        $this->assertStringContainsString('REPORT_LIMIT_BYTES = 262144', $diagnostic);
        $this->assertStringContainsString("'overall_status' => 'FAIL'", $diagnostic);
        $this->assertStringContainsString("'exception_class' => get_class($exception)", $diagnostic);
        $this->assertStringContainsString("fopen($temporary, 'x')", $diagnostic);
        $this->assertStringContainsString('rename($temporary, $reportPath)', $diagnostic);
        $this->assertStringContainsString('chmod($reportPath, 0600)', $diagnostic);
        $this->assertStringContainsString('refusing to overwrite report', $diagnostic);
        $this->assertStringNotContainsString('\\\\Illuminate\\\\', $diagnostic);
        $this->assertStringNotContainsString('artisan institutes:qualify-official-sources --', $workflow);
    }

    public function test_readonly_diagnostics_have_single_public_api_and_guest_access_checks(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/hostinger-readonly-diagnostics.yml'));

        $this->assertIsString($workflow);
        $this->assertSame(1, substr_count($workflow, '      - name: Verify public directory and API response contracts'));
        $this->assertSame(1, substr_count($workflow, '      - name: Verify guest denial for operator-only discovery endpoints'));
        $this->assertStringContainsString('live_guest_denial_status=PASS', $workflow);
        $this->assertStringNotContainsString('php artisan schedule:list --no-ansi', substr(
            $workflow,
            strpos($workflow, '      - name: Verify guest denial for operator-only discovery endpoints')
        ));
    }

    public function test_institution_field_approvals_schema_readiness_is_read_only_and_checks_structure_and_ledger(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/hostinger-readonly-diagnostics.yml'));

        $this->assertIsString($workflow);
        $this->assertStringContainsString('Institution field approvals schema and migration-ledger readiness (read-only)', $workflow);
        $this->assertStringContainsString('institution_field_approvals_schema_readiness', $workflow);
        $this->assertStringContainsString('2026_10_29_000001_create_institution_field_approvals_table', $workflow);
        $this->assertStringContainsString('getColumns($table)', $workflow);
        $this->assertStringContainsString('getIndexes($table)', $workflow);
        $this->assertStringContainsString('getForeignKeys($table)', $workflow);
        $this->assertStringContainsString('->where(\'migration\', $migration)', $workflow);
        $this->assertStringContainsString('PRESENT_AND_VERIFIED_EQUIVALENT', $workflow);
        $this->assertStringContainsString('PARTIALLY_APPLIED', $workflow);
        $this->assertStringContainsString('ABSENT', $workflow);
        $this->assertStringContainsString('AMBIGUOUS_UNVERIFIED', $workflow);
        $this->assertStringContainsString("'mutation_performed' => false", $workflow);
        $this->assertStringContainsString("'write_requested' => false", $workflow);
        $this->assertStringContainsString("base_path('database/migrations/'", $workflow);
        $this->assertStringContainsString('institution_field_approvals_migration_privilege_readiness', $workflow);
        $this->assertStringContainsString('SHOW GRANTS FOR CURRENT_USER()', $workflow);
        $this->assertStringContainsString("'raw_grants_emitted' => false", $workflow);
        $this->assertStringContainsString("'mutation_performed' => false", $workflow);
    }

    public function test_laravel_api_prefix_matches_the_public_institutes_subfolder(): void
    {
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

        $this->assertIsString($bootstrap);
        $this->assertStringContainsString("apiPrefix: 'institutes/api'", $bootstrap);
    }

    public function test_public_site_diagnostics_capture_status_content_type_and_json_pagination_contract(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/hostinger-readonly-diagnostics.yml'));

        $this->assertIsString($workflow);
        $this->assertStringContainsString('Verify public directory and API response contracts', $workflow);
        $this->assertStringContainsString('BASE_URL="https://estudent.pk"', $workflow);
        $this->assertStringContainsString('directory_page "$BASE_URL/institutes/" false', $workflow);
        $this->assertStringContainsString('declare -a api_candidates=(', $workflow);
        $this->assertStringContainsString('"$BASE_URL/institutes/api/v1/institutions"', $workflow);
        $this->assertStringContainsString('"$BASE_URL/institutes/v1/institutions"', $workflow);
        $this->assertStringContainsString('"$BASE_URL/api/v1/institutions"', $workflow);
        $this->assertStringContainsString('--max-redirs 0', $workflow);
        $this->assertStringContainsString('institutions_api_candidate_redirect=', $workflow);
        $this->assertStringContainsString('institutions_api_base_path=PASS', $workflow);
        $this->assertStringContainsString('institutions_api_base_path=FAIL_NO_VALID_JSON_ENDPOINT', $workflow);
        $this->assertStringContainsString('inspect_response institutions_api_page_1 "${api_base}?per_page=2" true', $workflow);
        $this->assertStringContainsString('inspect_response institutions_api_search "${api_base}?q=COMSATS&per_page=2" true', $workflow);
        $this->assertStringContainsString('inspect_response institutions_api_page_2 "${api_base}?per_page=2&page=2" true', $workflow);
        $this->assertStringContainsString('http_status=$status', $workflow);
        $this->assertStringContainsString('content_type=$content_type', $workflow);
        $this->assertStringContainsString('json_contract=PASS', $workflow);
        $this->assertStringContainsString('sanitized_body_prefix', $workflow);
        $this->assertStringContainsString('sanitized_response_body', $workflow);
        $this->assertStringContainsString('institutions_api_pagination=PASS', $workflow);
        $this->assertStringContainsString('(.data | type == "array")', $workflow);
        $this->assertStringContainsString('(.total | type == "number")', $workflow);
    }
}

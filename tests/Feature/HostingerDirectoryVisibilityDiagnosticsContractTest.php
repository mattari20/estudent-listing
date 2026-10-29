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
        $this->assertStringContainsString("'institutions', 'campuses', 'programs', 'program_offerings'", $workflow);
        $this->assertStringContainsString("'campuses_missing_institution'", $workflow);
        $this->assertStringContainsString("'programs_missing_institution'", $workflow);
        $this->assertStringContainsString("'offerings_missing_program'", $workflow);
        $this->assertStringContainsString("'offerings_missing_campus'", $workflow);
        $this->assertStringContainsString("'mutation_performed' => false", $workflow);
        $this->assertStringContainsString("'rows_by_status'", $workflow);
        $this->assertStringContainsString("'soft_deleted_rows'", $workflow);
        $this->assertStringContainsString('expected_hostinger_cron_command=', $workflow);
        $this->assertStringContainsString('artisan schedule:run', $workflow);
        $this->assertStringContainsString("git log --all --format='%H %cs %s' -S'duplicate_rows' -- database/migrations", $workflow);
        $this->assertStringContainsString("git log --all --format='%H %cs %s' -S'import_batches' -- database/migrations", $workflow);
    }

    public function test_public_site_diagnostics_capture_status_content_type_and_json_pagination_contract(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/hostinger-readonly-diagnostics.yml'));

        $this->assertIsString($workflow);
        $this->assertStringContainsString('Verify public directory and API response contracts', $workflow);
        $this->assertStringContainsString('BASE_URL="https://estudent.pk"', $workflow);
        $this->assertStringContainsString('directory_page "$BASE_URL/institutes/" false', $workflow);
        $this->assertStringContainsString('for candidate in "$BASE_URL/api/v1/institutions" "$BASE_URL/institutes/api/v1/institutions"; do', $workflow);
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

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
    }

    public function test_public_site_diagnostics_capture_status_content_type_and_json_pagination_contract(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/hostinger-readonly-diagnostics.yml'));

        $this->assertIsString($workflow);
        $this->assertStringContainsString('Verify public directory and API response contracts', $workflow);
        $this->assertStringContainsString('https://estudent.pk/institutes/', $workflow);
        $this->assertStringContainsString('https://estudent.pk/api/v1/institutions?per_page=2', $workflow);
        $this->assertStringContainsString('https://estudent.pk/api/v1/institutions?q=COMSATS&per_page=2', $workflow);
        $this->assertStringContainsString('https://estudent.pk/api/v1/institutions?per_page=2&page=2', $workflow);
        $this->assertStringContainsString('http_status=$status', $workflow);
        $this->assertStringContainsString('content_type=$content_type', $workflow);
        $this->assertStringContainsString('json_contract=PASS', $workflow);
        $this->assertStringContainsString('sanitized_body_prefix', $workflow);
        $this->assertStringContainsString('(.data | type == "array")', $workflow);
        $this->assertStringContainsString('(.total | type == "number")', $workflow);
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicBrowserRuntimeDiagnosticsContractTest extends TestCase
{
    public function test_clean_browser_workflow_captures_request_response_and_uncaught_json_errors(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/public-browser-runtime-diagnostics.yml'));

        $this->assertIsString($workflow);
        $this->assertStringContainsString('https://estudent.pk/institutes/', $workflow);
        $this->assertStringContainsString('https://estudent.pk/institutes/api/v1/institutions?per_page=2', $workflow);
        $this->assertStringContainsString('Network.requestWillBeSent', $workflow);
        $this->assertStringContainsString('Network.responseReceived', $workflow);
        $this->assertStringContainsString('initiatorFrames', $workflow);
        $this->assertStringContainsString('Runtime.exceptionThrown', $workflow);
        $this->assertStringContainsString('Unexpected end of JSON input', $workflow);
        $this->assertStringContainsString('browser_api_fetch_summary', $workflow);
        $this->assertStringContainsString('page_size_12', $workflow);
        $this->assertStringContainsString('search_get', $workflow);
        $this->assertStringContainsString('expected_empty_state_heading_missing', $workflow);
        $this->assertStringContainsString('jsonValid', $workflow);
        $this->assertStringContainsString('navigationStatuses', $workflow);
        $this->assertStringContainsString('search_navigation_not_http_200', $workflow);
        $this->assertStringContainsString('directory_reload_not_http_200', $workflow);
        $this->assertStringContainsString('page_size_navigation_not_http_200', $workflow);
        $this->assertStringContainsString('expected_empty_search_state_missing', $workflow);
        $this->assertStringContainsString('expected_empty_page_size_state_missing', $workflow);
        $this->assertStringNotContainsString("pageSummary('search_get', 200)", $workflow);
        $this->assertStringNotContainsString("pageSummary('page_size_12', 200)", $workflow);
        $this->assertStringNotContainsString('confirm_deployment', $workflow);
        $this->assertStringNotContainsString('confirm_migration', $workflow);
    }
}

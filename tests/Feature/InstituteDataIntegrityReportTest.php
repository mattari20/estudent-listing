<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class InstituteDataIntegrityReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_integrity_report_is_read_only_and_reports_expected_sections(): void
    {
        $exitCode = Artisan::call('institutes:data-integrity-report');
        $report = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame('institutes_data_integrity_report', $report['check']);
        $this->assertFalse($report['mutation_performed']);
        $this->assertArrayHasKey('canonical_counts', $report);
        $this->assertArrayHasKey('import_row_groups', $report);
        $this->assertArrayHasKey('promotion_event_types', $report);
        $this->assertArrayHasKey('promotion_failure_categories', $report);
        $this->assertArrayHasKey('readiness', $report);
        $this->assertArrayHasKey('warnings', $report);
    }
}

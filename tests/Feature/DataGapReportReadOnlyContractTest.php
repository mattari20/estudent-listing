<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DataGapReportReadOnlyContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_json_data_gap_report_declares_read_only_scope_and_no_mutation(): void
    {
        Artisan::call('institutes:data-gap-report', ['--json' => true]);
        $report = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('institutes_data_gap_report', $report['check']);
        $this->assertSame('read_only_production_data_diagnosis', $report['scope']);
        $this->assertFalse($report['mutation_performed']);
        $this->assertArrayHasKey('checked_at', $report);
        $this->assertArrayHasKey('coverage', $report['sections']);
    }
}

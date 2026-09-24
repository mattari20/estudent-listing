<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Services\InstituteOperatorReadinessDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteOperatorReadinessDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_is_read_only_and_reports_batch_readiness(): void
    {
        $summary = (new InstituteOperatorReadinessDashboardService)->summarize();

        $this->assertSame('read_only_operator_readiness_dashboard', $summary['scope']);
        $this->assertSame(0, $summary['batch_count']);
        $this->assertSame(0, $summary['ready_batches']);
        $this->assertSame(0, $summary['blocked_batches']);
        $this->assertFalse($summary['mutation_performed']);
    }
}

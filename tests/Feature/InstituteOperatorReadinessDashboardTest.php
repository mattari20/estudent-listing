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
        ImportBatch::query()->delete();

        $summary = (new InstituteOperatorReadinessDashboardService)->summarize();

        $this->assertSame('read_only_operator_readiness_dashboard', $summary['scope']);
        $this->assertSame(0, $summary['batch_count']);
        $this->assertSame(0, $summary['ready_batches']);
        $this->assertSame(0, $summary['blocked_batches']);
        $this->assertFalse($summary['mutation_performed']);
    }

    public function test_dashboard_preserves_existing_batch_status(): void
 {
  $batch = ImportBatch::create(['batch_key' => 'dashboard-readonly-'.uniqid(), 'entity_type' => 'institution', 'source_type' => 'manual', 'status' => 'validated', 'total_rows' => 0, 'valid_rows' => 0, 'invalid_rows' => 0, 'candidate_rows' => 0, 'received_at' => now()]);
  $summary = (new InstituteOperatorReadinessDashboardService)->summarize();
  $this->assertFalse($summary['mutation_performed']);
  $this->assertSame('validated', $batch->fresh()->status);
 }
}

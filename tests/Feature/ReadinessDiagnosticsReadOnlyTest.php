<?php

namespace Tests\Feature;

use App\Services\InstituteDataReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadinessDiagnosticsReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_summary_declares_read_only_scope_and_no_mutation(): void
    {
        $summary = app(InstituteDataReadinessService::class)->summarize();
        $this->assertSame('read_only_structural_readiness', $summary['scope']);
        $this->assertFalse($summary['mutation_performed']);
        $this->assertIsInt($summary['blocking_findings']);
    }
}

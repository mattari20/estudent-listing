<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Services\InstituteDataReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadinessPopulationCountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_summary_counts_canonical_institutions_without_mutation(): void
    {
        Institution::create(['name' => 'Readiness Count Active', 'slug' => 'readiness-count-active', 'status' => 'active']);
        Institution::create(['name' => 'Readiness Count Inactive', 'slug' => 'readiness-count-inactive', 'status' => 'inactive']);

        $summary = app(InstituteDataReadinessService::class)->summarize();
        $this->assertSame(2, $summary['population']['institutions']);
        $this->assertSame(1, $summary['population']['active_institutions']);
        $this->assertSame(1, $summary['population']['inactive_institutions']);
        $this->assertFalse($summary['mutation_performed']);
    }
}

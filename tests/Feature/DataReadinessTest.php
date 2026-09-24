<?php
namespace Tests\Feature;
use App\Models\Institution;
use App\Services\InstituteDataCoverageService;
use App\Services\InstituteDataReadinessService;
use Tests\TestCase;
class DataReadinessTest extends TestCase {
 public function test_readiness_summary_counts_institutions(): void { Institution::create(['name'=>'Example','slug'=>'example','status'=>'active']); $s=(new InstituteDataReadinessService)->summarize(); $this->assertSame(1,$s['institutions']); $this->assertSame(1,$s['active_institutions']); }
 public function test_coverage_is_explicit(): void { Institution::create(['name'=>'Example','slug'=>'example','status'=>'active','institution_type'=>'university']); $c=(new InstituteDataCoverageService)->institutionCoverage(); $this->assertSame(100.0,$c['type_coverage']); }
}
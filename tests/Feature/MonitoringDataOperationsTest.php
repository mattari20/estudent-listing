<?php
namespace Tests\Feature;
use App\Models\SourceMonitoringPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MonitoringDataOperationsTest extends TestCase {
 use RefreshDatabase;
 public function test_policy_state_is_explicit(): void { $p=SourceMonitoringPolicy::create(['name'=>'Admissions','fresh_after_days'=>30,'stale_after_days'=>90,'priority'=>'high','cadence'=>'daily','status'=>'active']); $this->assertSame('active',$p->status); }
}
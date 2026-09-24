<?php
namespace Tests\Feature;
use App\Models\SourceMonitoringPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MonitoringOperationsTest extends TestCase {
 use RefreshDatabase;
 public function test_policy_success_state_sets_next_run(): void {
  $p=SourceMonitoringPolicy::create(['name'=>'Daily','status'=>'active','cadence'=>'daily','fresh_after_days'=>30,'stale_after_days'=>90]);
  app(\App\Services\MonitoringPolicyStateService::class)->success($p);
  $this->assertNotNull($p->refresh()->next_run_at);
  $this->assertSame(0,$p->consecutive_failures);
 }
 public function test_dry_run_does_not_retrieve(): void {
  $p=SourceMonitoringPolicy::create(['name'=>'Dry','status'=>'active','source_id'=>null,'fresh_after_days'=>30,'stale_after_days'=>90]);
  $result=app(\App\Services\MonitoringDryRunService::class)->run($p->id);
  $this->assertSame(0,$result['sources']);
 }
}
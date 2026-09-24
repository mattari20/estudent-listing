<?php
namespace Tests\Feature;
use App\Models\SourceMonitoringPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MonitoringSchedulingGovernanceTest extends TestCase {
 use RefreshDatabase;
 public function test_due_policy_service_excludes_future_policy(): void {
  $p=SourceMonitoringPolicy::create(['name'=>'Future','status'=>'active','fresh_after_days'=>30,'stale_after_days'=>90,'next_run_at'=>now()->addDay()]);
  $this->assertCount(0,app(\App\Services\MonitoringDuePolicyService::class)->due());
  $p->update(['next_run_at'=>now()->subMinute()]);
  $this->assertCount(1,app(\App\Services\MonitoringDuePolicyService::class)->due());
 }
 public function test_invalid_cadence_is_rejected(): void {
  $p=SourceMonitoringPolicy::make(['fresh_after_days'=>30,'stale_after_days'=>90,'cadence'=>'yearly']);
  $this->expectException(\InvalidArgumentException::class);
  app(\App\Services\MonitoringPolicyValidationService::class)->validate($p);
 }
}
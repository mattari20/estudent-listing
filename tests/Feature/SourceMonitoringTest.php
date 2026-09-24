<?php
namespace Tests\Feature;
use App\Models\Evidence;
use App\Models\Source;
use App\Models\SourceMonitoringPolicy;
use App\Services\SourceMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SourceMonitoringTest extends TestCase {
 use RefreshDatabase;
 public function test_monitoring_uses_policy_thresholds_and_queues_stale_evidence(): void {
  $source=Source::create(['source_type'=>'institution','name'=>'Monitoring Source','status'=>'active']);
  $evidence=Evidence::create(['source_id'=>$source->id,'reference'=>'monitor','observed_at'=>now()->subDays(45),'status'=>'active']);
  $policy=SourceMonitoringPolicy::create(['name'=>'Official source policy','subject_type'=>null,'attribute'=>null,'fresh_after_days'=>14,'stale_after_days'=>30,'priority'=>'high','status'=>'active','source_id'=>$source->id]);
  $summary=app(SourceMonitoringService::class)->run($policy->id);
  $this->assertSame(1,$summary['stale']);
  $this->assertSame(1,$summary['queued']);
  $this->assertDatabaseHas('data_reverification_queue',['evidence_id'=>$evidence->id,'priority'=>'high','status'=>'open']);
  $this->assertNotNull($policy->refresh()->last_run_at);
 }
 public function test_monitoring_can_run_without_external_ai_or_crawler(): void {
  $summary=app(SourceMonitoringService::class)->run();
  $this->assertIsArray($summary);
  $this->assertArrayHasKey('policies',$summary);
 }
}
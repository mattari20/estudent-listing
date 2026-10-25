<?php
namespace Tests\Feature;
use App\Models\Evidence;
use App\Models\Source;
use App\Services\FreshnessReverificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class FreshnessReverificationTest extends TestCase {
 use RefreshDatabase;
 public function test_stale_evidence_creates_quality_event_and_reverification_item(): void {
  $source=Source::create(['source_type'=>'institution','name'=>'Test Source','status'=>'active']);
  $evidence=Evidence::create(['source_id'=>$source->id,'reference'=>'test','observed_at'=>now()->subDays(120),'status'=>'active']);
  $state=app(FreshnessReverificationService::class)->evaluate($evidence);
  $this->assertSame('stale',$state);
  $this->assertDatabaseHas('data_quality_events',['subject_type'=>Evidence::class,'subject_id'=>$evidence->id,'dimension'=>'freshness','state'=>'stale']);
  $this->assertDatabaseHas('data_reverification_queue',['evidence_id'=>$evidence->id,'reason'=>'stale_evidence','status'=>'open']);
 }

 public function test_existing_open_reverification_item_escalates_to_higher_priority_without_duplicate(): void {
  $source=Source::create(['source_type'=>'institution','name'=>'Escalation Source','status'=>'active']);
  $evidence=Evidence::create(['source_id'=>$source->id,'reference'=>'escalation-test','observed_at'=>now()->subDays(45),'status'=>'active']);
  $queue=app(\App\Services\ReverificationQueueService::class);
  $first=$queue->enqueueEvidence($evidence,'stale_evidence','normal');
  $originalObservedAt=$first->observed_at;
  $second=$queue->enqueueEvidence($evidence,'official_source_changed','high');
  $this->assertSame($first->id,$second->id);
  $this->assertSame('high',$second->priority);
  $this->assertSame('official_source_changed',$second->reason);
  $this->assertSame(1,\App\Models\DataReverificationQueue::where('evidence_id',$evidence->id)->where('status','open')->count());
  $this->assertEquals($originalObservedAt->timestamp,$second->fresh()->observed_at->timestamp);
 }

 public function test_reverification_priority_does_not_downgrade_existing_urgent_item(): void {
  $source=Source::create(['source_type'=>'institution','name'=>'Priority Source','status'=>'active']);
  $evidence=Evidence::create(['source_id'=>$source->id,'reference'=>'priority-test','observed_at'=>now()->subDays(45),'status'=>'active']);
  $queue=app(\App\Services\ReverificationQueueService::class);
  $first=$queue->enqueueEvidence($evidence,'official_source_changed','urgent');
  $second=$queue->enqueueEvidence($evidence,'stale_evidence','normal');
  $this->assertSame($first->id,$second->id);
  $this->assertSame('urgent',$second->priority);
  $this->assertSame('official_source_changed',$second->reason);
 }
}

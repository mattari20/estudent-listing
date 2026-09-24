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
}
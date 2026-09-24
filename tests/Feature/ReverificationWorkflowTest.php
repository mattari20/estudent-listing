<?php
namespace Tests\Feature;
use App\Models\DataReverificationQueue;
use App\Models\Institution;
use App\Models\Source;
use App\Models\Evidence;
use App\Models\User;
use App\Services\CanonicalRefreshService;
use App\Services\ReverificationWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ReverificationWorkflowTest extends TestCase {
 use RefreshDatabase;
 private function user(): User { return User::create(['name'=>'Operator','email'=>'rev-'.uniqid().'@example.test','password'=>'password','role'=>'operator']); }
 public function test_reverification_can_start_and_resolve_with_new_evidence(): void {
  $user=$this->user(); $source=Source::create(['source_type'=>'institution','name'=>'Official Source','status'=>'active']);
  $evidence=Evidence::create(['source_id'=>$source->id,'reference'=>'updated','observed_at'=>now(),'status'=>'active']);
  $item=DataReverificationQueue::create(['subject_type'=>Evidence::class,'subject_id'=>$evidence->id,'reason'=>'stale_evidence','priority'=>'high','status'=>'open','source_id'=>$source->id,'evidence_id'=>$evidence->id,'observed_at'=>now()]);
  app(ReverificationWorkflowService::class)->start($item,$user->id,'Begin review');
  app(ReverificationWorkflowService::class)->resolve($item,$user->id,'Verified current source',$evidence);
  $this->assertDatabaseHas('data_reverification_queue',['id'=>$item->id,'status'=>'resolved','evidence_id'=>$evidence->id]);
  $this->assertDatabaseHas('reverification_events',['queue_id'=>$item->id,'event_type'=>'review_resolved']);
 }
 public function test_canonical_refresh_records_old_and_new_values(): void {
  $user=$this->user(); $institution=Institution::create(['name'=>'Old Institute','slug'=>'old-institute','status'=>'active']);
  $item=DataReverificationQueue::create(['subject_type'=>Institution::class,'subject_id'=>$institution->id,'reason'=>'source_update','priority'=>'high','status'=>'in_review','observed_at'=>now()]);
  app(CanonicalRefreshService::class)->refreshInstitution($item,$user->id,['name'=>'Updated Institute'],'Verified current source');
  $institution->refresh();
  $this->assertSame('Updated Institute',$institution->name);
  $this->assertDatabaseHas('change_events',['subject_type'=>Institution::class,'subject_id'=>$institution->id,'event_type'=>'reverification_canonical_refresh','status'=>'applied']);
 }
}
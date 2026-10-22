<?php
namespace Tests\Feature;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ImportConflictDetectionTest extends TestCase {
 use RefreshDatabase;
 public function test_duplicate_batch_identity_creates_conflict_and_blocks_promotion(): void {
  $user=User::create(['name'=>'Operator','email'=>'conflict-'.uniqid().'@example.test','password'=>'password','role'=>'operator']);
  $batch=ImportBatch::create(['batch_key'=>'conflict-'.uniqid(),'entity_type'=>'institution','source_type'=>'manual','status'=>'validated','total_rows'=>2,'valid_rows'=>2,'candidate_rows'=>2,'received_at'=>now()]);
  $row1=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'candidate','match_state'=>'new_candidate','payload'=>['name'=>'Same Institute'],'normalized_payload'=>['name'=>'Same Institute','slug'=>'same-institute']]);
  $row2=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>2,'status'=>'candidate','match_state'=>'new_candidate','payload'=>['name'=>'Same Institute 2'],'normalized_payload'=>['name'=>'Same Institute 2','slug'=>'same-institute']]);
  $this->actingAs($user)->post(route('institutes.operations.resolve',$row1))->assertRedirect();
  $this->assertDatabaseHas('import_conflicts',['import_row_id'=>$row1->id,'conflict_type'=>'duplicate_batch_identity','status'=>'open']);
  $this->assertSame('conflict',$row1->refresh()->match_state);
 }

    public function test_duplicate_identity_conflict_is_not_auto_resolved(): void
 {
  $batch=ImportBatch::create(['batch_key'=>'conflict-review-'.uniqid(),'entity_type'=>'institution','source_type'=>'manual','status'=>'validated','total_rows'=>2,'valid_rows'=>2,'candidate_rows'=>2,'received_at'=>now()]);
  $row1=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'candidate','match_state'=>'new_candidate','payload'=>['name'=>'Reviewed Institute'],'normalized_payload'=>['name'=>'Reviewed Institute','slug'=>'reviewed-institute']]);
  $row2=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>2,'status'=>'candidate','match_state'=>'new_candidate','payload'=>['name'=>'Reviewed Institute branch'],'normalized_payload'=>['name'=>'Reviewed Institute branch','slug'=>'reviewed-institute']]);
  $this->post(route('institutes.operations.resolve',$row1))->assertRedirect();
  $this->assertDatabaseHas('import_conflicts',['import_row_id'=>$row1->id,'conflict_type'=>'duplicate_batch_identity','status'=>'open']);
 }
}
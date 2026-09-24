<?php
namespace Tests\Feature;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ImportPromotionReversalTest extends TestCase {
 use RefreshDatabase;
 public function test_new_institution_promotion_can_be_reversed_without_hard_delete(): void {
  $user=User::create(['name'=>'Operator','email'=>'operator-'.uniqid().'@example.test','password'=>'password','role'=>'operator']);
  $batch=ImportBatch::create(['batch_key'=>'reverse-'.uniqid(),'entity_type'=>'institution','source_type'=>'manual','status'=>'validated','total_rows'=>1,'valid_rows'=>1,'candidate_rows'=>1,'received_at'=>now()]);
  $row=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'candidate','match_state'=>'new_candidate','payload'=>['name'=>'Reversible Institute'],'normalized_payload'=>['name'=>'Reversible Institute','slug'=>'reversible-institute']]);
  $this->actingAs($user)->post(route('institutes.operations.decide',$row),['decision'=>'approved_new','reason'=>'Approved'])->assertRedirect();
  $this->actingAs($user)->post(route('institutes.operations.promote',$row))->assertRedirect();
  $row->refresh(); $institution=Institution::findOrFail($row->canonical_subject_id);
  $this->actingAs($user)->post(route('institutes.operations.reverse',$row),['reason'=>'Imported record was incorrect'])->assertRedirect();
  $row->refresh(); $institution->refresh();
  $this->assertSame('reversed',$row->status);
  $this->assertSame('superseded',$institution->status);
  $this->assertDatabaseHas('import_promotion_events',['import_row_id'=>$row->id,'event_type'=>'promotion_reversed']);
 }
 public function test_reversal_does_not_remove_existing_match(): void {
  $user=User::create(['name'=>'Operator','email'=>'operator-'.uniqid().'@example.test','password'=>'password','role'=>'operator']);
  $institution=Institution::create(['name'=>'Existing Institute','slug'=>'existing-institute','status'=>'active']);
  $batch=ImportBatch::create(['batch_key'=>'match-'.uniqid(),'entity_type'=>'institution','source_type'=>'manual','status'=>'validated','total_rows'=>1,'valid_rows'=>1,'candidate_rows'=>1,'received_at'=>now()]);
  $row=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'candidate','match_state'=>'matched','payload'=>['name'=>'Existing Institute'],'normalized_payload'=>['name'=>'Existing Institute','slug'=>'existing-institute']]);
  $this->actingAs($user)->post(route('institutes.operations.decide',$row),['decision'=>'approved_match','institution_id'=>$institution->id,'reason'=>'Existing identity confirmed'])->assertRedirect();
  $this->actingAs($user)->post(route('institutes.operations.promote',$row))->assertRedirect();
  $this->actingAs($user)->post(route('institutes.operations.reverse',$row),['reason'=>'Import row withdrawn'])->assertRedirect();
  $institution->refresh();
  $this->assertSame('active',$institution->status);
 }
}
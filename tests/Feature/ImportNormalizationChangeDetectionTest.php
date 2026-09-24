<?php
namespace Tests\Feature;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\User;
use App\Models\ChangeEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ImportNormalizationChangeDetectionTest extends TestCase {
 use RefreshDatabase;
 public function test_import_normalization_creates_stable_comparison_form(): void {
  $batch=ImportBatch::create(['batch_key'=>'norm-'.uniqid(),'entity_type'=>'institution','source_type'=>'manual','status'=>'validated','total_rows'=>1,'received_at'=>now()]);
  $row=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'candidate','match_state'=>'unresolved','payload'=>['name'=>'  Example   Institute  '],'normalized_payload'=>app(\App\Services\InstituteImportControllerSupport::class)->normalize(['name'=>'  Example   Institute  '])]);
  $this->assertSame('Example Institute',$row->normalized_payload['name']);
  $this->assertSame('example-institute',$row->normalized_payload['slug']);
 }
 public function test_existing_match_difference_creates_change_event_and_conflict(): void {
  $user=User::create(['name'=>'Operator','email'=>'change-'.uniqid().'@example.test','password'=>'password','role'=>'operator']);
  $institution=Institution::create(['name'=>'Canonical Institute','slug'=>'canonical-institute','status'=>'active']);
  $batch=ImportBatch::create(['batch_key'=>'compare-'.uniqid(),'entity_type'=>'institution','source_type'=>'manual','status'=>'validated','total_rows'=>1,'valid_rows'=>1,'candidate_rows'=>1,'received_at'=>now()]);
  $row=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'candidate','match_state'=>'matched','payload'=>['name'=>'Canonical Institute'],'normalized_payload'=>['name'=>'Canonical Institute','slug'=>'canonical-institute','status'=>'inactive']]);
  $this->actingAs($user)->post(route('institutes.operations.decide',$row),['decision'=>'approved_match','institution_id'=>$institution->id,'reason'=>'Identity confirmed'])->assertRedirect();
  $response=$this->actingAs($user)->post(route('institutes.operations.promote',$row));
  $response->assertSessionHasErrors('promotion');
  $this->assertDatabaseHas('import_conflicts',['import_row_id'=>$row->id,'conflict_type'=>'cross_source_attribute_difference','status'=>'open']);
  $this->assertDatabaseCount('change_events',0);
 }
}
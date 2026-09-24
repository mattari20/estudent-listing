<?php
namespace Tests\Feature;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ImportPromotionAuditTest extends TestCase {
 use RefreshDatabase;
 public function test_new_institution_promotion_creates_durable_audit_event(): void {
  $user=User::create(['name'=>'Operator','email'=>'operator-'.uniqid().'@example.test','password'=>'password','role'=>'operator']);
  $batch=ImportBatch::create(['batch_key'=>'audit-'.uniqid(),'entity_type'=>'institution','source_type'=>'manual','status'=>'validated','total_rows'=>1,'valid_rows'=>1,'candidate_rows'=>1,'received_at'=>now()]);
  $row=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'candidate','match_state'=>'new_candidate','payload'=>['name'=>'Audit Institute'],'normalized_payload'=>['name'=>'Audit Institute','slug'=>'audit-institute']]);
  $this->actingAs($user)->post(route('institutes.operations.decide',$row),['decision'=>'approved_new','reason'=>'Explicit operator approval'])->assertRedirect();
  $this->actingAs($user)->post(route('institutes.operations.promote',$row))->assertRedirect();
  $this->assertDatabaseHas('import_promotion_events',['import_row_id'=>$row->id,'event_type'=>'promotion_succeeded','decision_type'=>'identity_resolution']);
 }
}
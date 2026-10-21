<?php
namespace Tests\Feature;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Source;
use App\Models\Evidence;
use App\Models\Assertion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ImportPromotionAuditTest extends TestCase {
 use RefreshDatabase;
 public function test_new_institution_promotion_creates_durable_audit_event(): void {
  $user=User::create(['name'=>'Operator','email'=>'operator-'.uniqid().'@example.test','password'=>'password','role'=>'operator']);
  $source=Source::create(['source_type'=>'official','name'=>'Audit Source','url'=>'https://example.test/source','status'=>'active']);
  $batch=ImportBatch::create(['batch_key'=>'audit-'.uniqid(),'entity_type'=>'institution','source_type'=>'official','source_id'=>$source->id,'status'=>'validated','total_rows'=>1,'valid_rows'=>1,'candidate_rows'=>1,'received_at'=>now()]);
  $row=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'candidate','match_state'=>'new_candidate','payload'=>['name'=>'Audit Institute'],'normalized_payload'=>['name'=>'Audit Institute','slug'=>'audit-institute']]);
  $evidence=Evidence::create(['source_id'=>$source->id,'reference'=>'https://example.test/institute','excerpt'=>'Official institute listing','content_hash'=>'audit-evidence-'.$row->id,'observed_at'=>now(),'status'=>'active']);
  Assertion::create(['evidence_id'=>$evidence->id,'subject_type'=>ImportRow::class,'subject_id'=>$row->id,'attribute'=>'source_record','value'=>'official','verification_state'=>'observed','confidence'=>1]);
  $this->actingAs($user)->post(route('institutes.operations.decide',$row),['decision'=>'approved_new','reason'=>'Explicit operator approval'])->assertRedirect();
  $this->actingAs($user)->post(route('institutes.operations.promote',$row))->assertRedirect();
  $this->assertDatabaseHas('import_promotion_events',['import_row_id'=>$row->id,'event_type'=>'promotion_succeeded','decision_type'=>'identity_resolution']);
  $row->refresh();
  $this->assertSame('promoted',$row->status);
  $institutionId=$row->canonical_subject_id;
  $this->assertNotNull($institutionId);
  $this->assertDatabaseHas('institutions',['id'=>$institutionId,'status'=>'candidate']);
  $this->actingAs($user)->post(route('institutes.operations.publish',$row))->assertRedirect();
  $this->assertDatabaseHas('institutions',['id'=>$institutionId,'status'=>'active']);
  $this->assertDatabaseHas('import_promotion_events',['import_row_id'=>$row->id,'event_type'=>'institution_published','decision_type'=>'identity_resolution']);
 }
}
<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class InstituteOperationsAccessTest extends TestCase {
 use RefreshDatabase;
 public function test_operations_require_login(): void { $this->get('/institutes/operations')->assertRedirect('/institutes/login'); }
 public function test_non_operator_cannot_access_operations(): void {
  $user=User::create(['name'=>'Viewer','email'=>'viewer@example.test','password'=>'secret','role'=>'viewer']);
  $this->actingAs($user)->get('/institutes/operations')->assertRedirect('/institutes/login');
 }
 public function test_operator_can_access_review_queue(): void {
  $user=User::create(['name'=>'Operator','email'=>'operator@example.test','password'=>'secret','role'=>'operator']);
  $this->actingAs($user)->get('/institutes/operations')->assertOk();
 }
 public function test_import_requires_operator(): void { $this->get('/institutes/operations/import')->assertRedirect('/institutes/login'); }

    public function test_row_review_route_requires_authentication(): void { $this->get('/institutes/operations/rows/1')->assertRedirect('/institutes/login'); }

    public function test_viewer_cannot_open_import_review_route(): void
 {
  $user = User::create(['name' => 'Read Only Viewer', 'email' => 'viewer-import-'.uniqid().'@example.test', 'password' => 'secret', 'role' => 'viewer']);
  $this->actingAs($user)->get('/institutes/operations/import')->assertRedirect('/institutes/login');
 }


 public function test_viewer_cannot_submit_promotion_decision(): void {
  $viewer=User::create(['name'=>'Decision Viewer','email'=>'decision-viewer-'.uniqid().'@example.test','password'=>'secret','role'=>'viewer']);
  $batch=ImportBatch::create(['batch_key'=>'viewer-decision-'.uniqid(),'entity_type'=>'institution','source_type'=>'manual','status'=>'validated','total_rows'=>1,'valid_rows'=>1,'candidate_rows'=>1,'received_at'=>now()]);
  $row=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'candidate','match_state'=>'new_candidate','payload'=>['name'=>'Viewer Protected Institute'],'normalized_payload'=>['name'=>'Viewer Protected Institute']]);

  $this->actingAs($viewer)->post(route('institutes.operations.decide',$row),['decision'=>'approved_new','reason'=>'Should be blocked'])
   ->assertRedirect('/institutes/login');
  $this->assertDatabaseMissing('identity_resolution_decisions',['import_row_id'=>$row->id,'decision'=>'approved_new']);
 }
}
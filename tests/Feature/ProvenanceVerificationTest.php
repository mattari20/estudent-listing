<?php
namespace Tests\Feature;
use App\Models\Assertion;
use App\Models\Evidence;
use App\Models\Source;
use App\Services\AssertionVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ProvenanceVerificationTest extends TestCase {
 use RefreshDatabase;
 private function assertion(): Assertion {
  $source=Source::create(['source_type'=>'primary','name'=>'Official Source','status'=>'active']);
  $evidence=Evidence::create(['source_id'=>$source->id,'reference'=>'test-ref','excerpt'=>'Official evidence','status'=>'active','observed_at'=>now()]);
  return Assertion::create(['evidence_id'=>$evidence->id,'subject_type'=>'institution','subject_id'=>1,'attribute'=>'status','value'=>'active','verification_state'=>'under_review']);
 }
 public function test_verification_decision_preserves_previous_and_new_state(): void {
  $assertion=$this->assertion();
  $decision=app(AssertionVerificationService::class)->decide($assertion,'verified',1,'Reviewed against evidence.');
  $this->assertSame('under_review',$decision->previous_state);
  $this->assertSame('verified',$decision->new_state);
  $this->assertSame('verified',$assertion->fresh()->verification_state);
  $this->assertDatabaseHas('provenance_review_events',['subject_type'=>Assertion::class,'subject_id'=>$assertion->id,'event_type'=>'verification_state_changed']);
 }
 public function test_invalid_state_is_rejected(): void {
  $this->expectException(\RuntimeException::class);
  app(AssertionVerificationService::class)->decide($this->assertion(),'approved',1,'Invalid');
 }
}
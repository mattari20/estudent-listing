<?php
namespace Tests\Feature;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Evidence;
use App\Models\Assertion;
use App\Models\Institution;
use App\Models\InstitutionName;
use App\Models\Source;
use App\Services\InstituteIdentityResolver;
use App\Services\InstituteImportPromoter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class IdentityResolutionTest extends TestCase {
 use RefreshDatabase;
 private function attachEvidence(ImportRow $row): void {
  $source=$row->batch->source;
  $evidence=Evidence::create(['source_id'=>$source->id,'reference'=>'https://example.test/evidence-'.$row->id,'excerpt'=>'Official source confirms institution','content_hash'=>'identity-evidence-'.$row->id,'observed_at'=>now(),'status'=>'active']);
  Assertion::create(['evidence_id'=>$evidence->id,'subject_type'=>ImportRow::class,'subject_id'=>$row->id,'attribute'=>'source_record','value'=>'official','verification_state'=>'observed','confidence'=>1]);
 }
 private function row(array $payload): ImportRow {
  $source=Source::create(['source_type'=>'primary','name'=>'Test Source','status'=>'active']);
  $batch=ImportBatch::create(['batch_key'=>uniqid('batch-'),'entity_type'=>'institution','source_type'=>'primary','source_id'=>$source->id,'status'=>'validated']);
  return ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'candidate','match_state'=>'unresolved','payload'=>$payload,'normalized_payload'=>$payload]);
 }
 public function test_exact_slug_is_resolved_as_candidate_match(): void {
  $institution=Institution::create(['name'=>'Example University','slug'=>'example-university','status'=>'active']);
  $row=$this->row(['name'=>'Example University','slug'=>'example-university']);
  $count=app(InstituteIdentityResolver::class)->resolve($row);
  $this->assertSame(1,$count);
  $this->assertDatabaseHas('identity_match_candidates',['import_row_id'=>$row->id,'institution_id'=>$institution->id,'match_type'=>'exact_slug','status'=>'candidate']);
  $this->assertSame('matched',$row->fresh()->match_state);
 }
 public function test_official_name_and_short_name_can_match_existing_institution(): void {
  $institution=Institution::create(['name'=>'National University of Example','official_name'=>'National University of Example Pakistan','short_name'=>'NUE','slug'=>'nue','status'=>'active']);
  $row=$this->row(['name'=>'NUE','official_name'=>'National University of Example Pakistan','slug'=>'candidate-nue']);
  $count=app(InstituteIdentityResolver::class)->resolve($row);
  $this->assertSame(1,$count);
  $this->assertDatabaseHas('identity_match_candidates',['import_row_id'=>$row->id,'institution_id'=>$institution->id,'match_type'=>'exact_official_name','status'=>'candidate']);
 }
 public function test_recorded_alias_can_match_existing_institution(): void {
  $institution=Institution::create(['name'=>'Example University','slug'=>'example-university','status'=>'active']);
  InstitutionName::create(['institution_id'=>$institution->id,'name'=>'Example Institute','name_type'=>'former_name','language'=>'en','is_current'=>false]);
  $row=$this->row(['name'=>'Example Institute','slug'=>'example-institute']);
  $count=app(InstituteIdentityResolver::class)->resolve($row);
  $this->assertSame(1,$count);
  $this->assertDatabaseHas('identity_match_candidates',['import_row_id'=>$row->id,'institution_id'=>$institution->id,'match_type'=>'exact_alias','status'=>'candidate']);
 }
 public function test_unmatched_row_becomes_new_candidate_without_creating_institution(): void {
  $row=$this->row(['name'=>'New University','slug'=>'new-university']);
  app(InstituteIdentityResolver::class)->resolve($row);
  $this->assertSame('new_candidate',$row->fresh()->match_state);
  $this->assertDatabaseCount('institutions',0);
 }
 public function test_promotion_requires_explicit_approved_decision(): void {
  $row=$this->row(['name'=>'New University','slug'=>'new-university']);
  app(InstituteIdentityResolver::class)->resolve($row);
  $this->attachEvidence($row);
  $decision=IdentityResolutionDecision::create(['import_row_id'=>$row->id,'institution_id'=>null,'decision'=>'approved_new','actor_type'=>'test','reason'=>'Reviewed','decided_at'=>now()]);
  $institution=app(InstituteImportPromoter::class)->promote($row,$decision);
  $this->assertDatabaseHas('institutions',['id'=>$institution->id,'slug'=>'new-university']);
  $this->assertDatabaseHas('institution_names',['institution_id'=>$institution->id,'name'=>'New University']);
 }
 public function test_match_promotion_never_creates_duplicate_institution(): void {
  $institution=Institution::create(['name'=>'Example University','slug'=>'example-university','status'=>'active']);
  $row=$this->row(['name'=>'Example University','slug'=>'example-university']);
  app(InstituteIdentityResolver::class)->resolve($row);
  $this->attachEvidence($row);
  $decision=IdentityResolutionDecision::create(['import_row_id'=>$row->id,'institution_id'=>$institution->id,'decision'=>'approved_match','actor_type'=>'test','reason'=>'Confirmed','decided_at'=>now()]);
  $result=app(InstituteImportPromoter::class)->promote($row,$decision);
  $this->assertSame($institution->id,$result->id);
  $this->assertDatabaseCount('institutions',1);
 }
}

<?php
namespace Tests\Feature;
use App\Models\ImportBatch;
use App\Models\ImportPromotionDecision;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Program;
use App\Services\EnrichmentPromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class EnrichmentDependencySafetyTest extends TestCase {
 use RefreshDatabase;
 public function test_program_requires_institution_parent(): void {
  $b=ImportBatch::create(['batch_key'=>'dep-program','entity_type'=>'program','source_type'=>'primary']);
  $r=ImportRow::create(['import_batch_id'=>$b->id,'row_number'=>1,'status'=>'candidate','payload'=>['name'=>'BS CS'],'normalized_payload'=>['name'=>'BS CS']]);
  $this->expectException(\RuntimeException::class); app(EnrichmentPromotionService::class)->validateParent($r);
 }
 public function test_offering_rejects_institution_as_parent(): void {
  $b=ImportBatch::create(['batch_key'=>'dep-offering','entity_type'=>'program_offering','source_type'=>'primary']);
  $i=Institution::create(['name'=>'Example','slug'=>'example','status'=>'active']);
  $r=ImportRow::create(['import_batch_id'=>$b->id,'row_number'=>1,'status'=>'candidate','payload'=>[],'normalized_payload'=>[],'parent_subject_type'=>Institution::class,'parent_subject_id'=>$i->id]);
  $this->expectException(\RuntimeException::class); app(EnrichmentPromotionService::class)->validateParent($r);
 }
 public function test_offering_accepts_program_parent(): void {
  $b=ImportBatch::create(['batch_key'=>'dep-offering-valid','entity_type'=>'program_offering','source_type'=>'primary']);
  $i=Institution::create(['name'=>'Example','slug'=>'example','status'=>'active']);
  $p=Program::create(['institution_id'=>$i->id,'name'=>'BS CS','slug'=>'bs-cs','status'=>'active']);
  $r=ImportRow::create(['import_batch_id'=>$b->id,'row_number'=>1,'status'=>'candidate','payload'=>[],'normalized_payload'=>[],'parent_subject_type'=>Program::class,'parent_subject_id'=>$p->id]);
  app(EnrichmentPromotionService::class)->validateParent($r); $this->assertTrue(true);
 }
 public function test_enrichment_has_separate_promotion_decision_record(): void {
  $b=ImportBatch::create(['batch_key'=>'dep-decision','entity_type'=>'program','source_type'=>'primary']);
  $r=ImportRow::create(['import_batch_id'=>$b->id,'row_number'=>1,'status'=>'candidate','payload'=>['name'=>'BS CS'],'normalized_payload'=>['name'=>'BS CS']]);
  ImportPromotionDecision::create(['import_row_id'=>$r->id,'decision'=>'approved','actor_type'=>'user','actor_id'=>1,'reason'=>'Reviewed','decided_at'=>now()]);
  $this->assertDatabaseHas('import_promotion_decisions',['import_row_id'=>$r->id,'decision'=>'approved']);
 }
}
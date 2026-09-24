<?php
namespace Tests\Feature;
use App\Models\ImportBatch;
use App\Models\ImportPromotionDecision;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Program;
use App\Services\ImportDependencyContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class EnrichmentReviewWorkflowTest extends TestCase {
 use RefreshDatabase;
 public function test_dependency_context_builds_institution_program_chain(): void {
  $i=Institution::create(['name'=>'Example','slug'=>'example','status'=>'active']);
  $p=Program::create(['institution_id'=>$i->id,'name'=>'BS CS','slug'=>'bs-cs','status'=>'active']);
  $b=ImportBatch::create(['batch_key'=>'review-chain','entity_type'=>'program_offering','source_type'=>'primary']);
  $r=ImportRow::create(['import_batch_id'=>$b->id,'row_number'=>1,'status'=>'candidate','payload'=>[],'normalized_payload'=>[],'parent_subject_type'=>Program::class,'parent_subject_id'=>$p->id]);
  $ctx=app(ImportDependencyContextService::class)->build($r);
  $this->assertCount(2,$ctx['chain']); $this->assertSame($i->id,$ctx['chain'][0]->id); $this->assertSame($p->id,$ctx['chain'][1]->id);
 }
 public function test_enrichment_decision_can_be_recorded(): void {
  $b=ImportBatch::create(['batch_key'=>'review-decision','entity_type'=>'program','source_type'=>'primary']);
  $r=ImportRow::create(['import_batch_id'=>$b->id,'row_number'=>1,'status'=>'candidate','payload'=>['name'=>'BS CS'],'normalized_payload'=>['name'=>'BS CS'],'parent_subject_type'=>Institution::class,'parent_subject_id'=>1]);
  ImportPromotionDecision::create(['import_row_id'=>$r->id,'decision'=>'approved','actor_type'=>'user','actor_id'=>1,'reason'=>'Reviewed','decided_at'=>now()]);
  $this->assertDatabaseHas('import_promotion_decisions',['import_row_id'=>$r->id,'decision'=>'approved']);
 }
}
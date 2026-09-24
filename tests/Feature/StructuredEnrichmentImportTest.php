<?php
namespace Tests\Feature;
use App\Models\EnrichmentImportRow;
use App\Models\ImportBatch;
use App\Models\Institution;
use App\Services\StructuredEnrichmentPromoter;
use App\Services\StructuredEnrichmentValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class StructuredEnrichmentImportTest extends TestCase {
 use RefreshDatabase;
 private function batch(): ImportBatch { return ImportBatch::create(['batch_key'=>uniqid('enrich-'),'entity_type'=>'enrichment','source_type'=>'primary','status'=>'validated']); }
 public function test_campus_requires_institution_and_name(): void { $this->expectException(\InvalidArgumentException::class); app(StructuredEnrichmentValidator::class)->normalize('campus',['name'=>'Lahore']); }
 public function test_approved_program_can_be_promoted(): void {
  $institution=Institution::create(['name'=>'Test University','slug'=>'test-university','status'=>'active']);
  $row=EnrichmentImportRow::create(['import_batch_id'=>$this->batch()->id,'entity_type'=>'program','status'=>'approved','match_state'=>'matched','payload'=>['institution_id'=>$institution->id,'name'=>'BS Computer Science'],'normalized_payload'=>['institution_id'=>$institution->id,'name'=>'BS Computer Science','slug'=>'bs-computer-science']]);
  $program=app(StructuredEnrichmentPromoter::class)->promote($row);
  $this->assertSame('BS Computer Science',$program->name);
  $this->assertDatabaseHas('programs',['institution_id'=>$institution->id,'slug'=>'bs-computer-science']);
 }
 public function test_unapproved_enrichment_cannot_promote(): void {
  $row=EnrichmentImportRow::create(['import_batch_id'=>$this->batch()->id,'entity_type'=>'program','status'=>'candidate','payload'=>['institution_id'=>1,'name'=>'Unsafe']]);
  $this->expectException(\RuntimeException::class); app(StructuredEnrichmentPromoter::class)->promote($row);
 }
}
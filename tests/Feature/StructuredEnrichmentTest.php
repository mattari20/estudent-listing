<?php
namespace Tests\Feature;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Program;
use App\Services\StructuredEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class StructuredEnrichmentTest extends TestCase {
 use RefreshDatabase;
 private function row(string $type,array $payload,object $parent): ImportRow {
  $batch=ImportBatch::create(['batch_key'=>uniqid('enrich-'),'entity_type'=>$type,'source_type'=>'primary','status'=>'validated']);
  return ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'candidate','match_state'=>'resolved','parent_subject_type'=>$parent::class,'parent_subject_id'=>$parent->id,'payload'=>$payload,'normalized_payload'=>$payload]);
 }
 public function test_program_enrichment_requires_canonical_institution(): void {
  $i=Institution::create(['name'=>'Example University','slug'=>'example-university','status'=>'active']);
  $row=$this->row('program',['name'=>'BS Computer Science','slug'=>'bs-computer-science'],$i);
  $program=app(StructuredEnrichmentService::class)->promote($row);
  $this->assertInstanceOf(Program::class,$program);
  $this->assertSame($i->id,$program->institution_id);
 }
 public function test_enrichment_is_not_allowed_for_unresolved_parent(): void {
  $batch=ImportBatch::create(['batch_key'=>uniqid('enrich-'),'entity_type'=>'program','source_type'=>'primary','status'=>'validated']);
  $row=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'candidate','payload'=>['name'=>'Test'],'normalized_payload'=>['name'=>'Test']]);
  $this->expectException(\RuntimeException::class);
  app(StructuredEnrichmentService::class)->promote($row);
 }
}
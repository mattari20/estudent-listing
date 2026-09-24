<?php
namespace Tests\Feature;
use App\Models\AdmissionIntake;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Services\AcademicEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AcademicEnrichmentTest extends TestCase {
 use RefreshDatabase;
 private function row(string $type,array $payload,object $parent): ImportRow {
  $b=ImportBatch::create(['batch_key'=>uniqid('academic-'),'entity_type'=>$type,'source_type'=>'primary','status'=>'validated']);
  return ImportRow::create(['import_batch_id'=>$b->id,'row_number'=>1,'status'=>'candidate','match_state'=>'resolved','parent_subject_type'=>$parent::class,'parent_subject_id'=>$parent->id,'payload'=>$payload,'normalized_payload'=>$payload]);
 }
 private function offering(): ProgramOffering {
  $i=Institution::create(['name'=>'Example University','slug'=>'example-university','status'=>'active']);
  $p=Program::create(['institution_id'=>$i->id,'name'=>'BS CS','slug'=>'bs-cs','status'=>'active']);
  return ProgramOffering::create(['program_id'=>$p->id,'status'=>'active','delivery_mode'=>'on-campus','intake_label'=>'Fall']);
 }
 public function test_intake_requires_offering(): void {
  $o=$this->offering(); $row=$this->row('admission_intake',['label'=>'Fall 2027'],$o);
  $x=app(AcademicEnrichmentService::class)->promote($row); $this->assertInstanceOf(AdmissionIntake::class,$x);
 }
 public function test_eligibility_requires_intake(): void {
  $o=$this->offering(); $i=AdmissionIntake::create(['program_offering_id'=>$o->id,'label'=>'Fall 2027','status'=>'planned']);
  $row=$this->row('eligibility_rule',['requirement'=>'Intermediate or equivalent'],$i);
  $x=app(AcademicEnrichmentService::class)->promote($row); $this->assertSame($i->id,$x->admission_intake_id);
 }
 public function test_unparented_academic_row_fails(): void {
  $b=ImportBatch::create(['batch_key'=>uniqid('academic-'),'entity_type'=>'merit_record','source_type'=>'primary','status'=>'validated']);
  $row=ImportRow::create(['import_batch_id'=>$b->id,'row_number'=>1,'status'=>'candidate','payload'=>['merit_type'=>'closing'],'normalized_payload'=>['merit_type'=>'closing']]);
  $this->expectException(\RuntimeException::class); app(AcademicEnrichmentService::class)->promote($row);
 }
}
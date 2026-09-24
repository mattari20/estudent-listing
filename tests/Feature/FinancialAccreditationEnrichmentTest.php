<?php
namespace Tests\Feature;
use App\Models\AdmissionIntake;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Models\Scholarship;
use App\Services\FinancialAccreditationEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class FinancialAccreditationEnrichmentTest extends TestCase {
 use RefreshDatabase;
 private function row(string $type,array $payload,object $parent): ImportRow {
  $b=ImportBatch::create(['batch_key'=>uniqid('finance-'),'entity_type'=>$type,'source_type'=>'primary','status'=>'validated']);
  return ImportRow::create(['import_batch_id'=>$b->id,'row_number'=>1,'status'=>'candidate','match_state'=>'resolved','parent_subject_type'=>$parent::class,'parent_subject_id'=>$parent->id,'payload'=>$payload,'normalized_payload'=>$payload]);
 }
 private function intake(): AdmissionIntake {
  $i=Institution::create(['name'=>'Example University','slug'=>'example-university','status'=>'active']);
  $p=Program::create(['institution_id'=>$i->id,'name'=>'BS CS','slug'=>'bs-cs','status'=>'active']);
  $o=ProgramOffering::create(['program_id'=>$p->id,'status'=>'active','delivery_mode'=>'on-campus','intake_label'=>'Fall']);
  return AdmissionIntake::create(['program_offering_id'=>$o->id,'label'=>'Fall 2027','status'=>'planned']);
 }
 public function test_fee_requires_recorded_value_or_note(): void {
  $i=$this->intake(); $row=$this->row('fee_record',['fee_type'=>'tuition'],$i);
  $this->expectException(\RuntimeException::class); app(FinancialAccreditationEnrichmentService::class)->promote($row);
 }
 public function test_scholarship_is_optional_data_and_can_be_recorded(): void {
  $i=$this->intake(); $row=$this->row('scholarship',['name'=>'Need Based Aid','data_state'=>'awaiting_verification'],$i);
  $x=app(FinancialAccreditationEnrichmentService::class)->promote($row);
  $this->assertInstanceOf(Scholarship::class,$x); $this->assertSame('awaiting_verification',$x->data_state);
 }
}
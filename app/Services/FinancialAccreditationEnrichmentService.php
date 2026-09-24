<?php
namespace App\Services;
use App\Models\Accreditation;
use App\Models\FeeRecord;
use App\Models\ImportRow;
use App\Models\Scholarship;
use App\Models\ProgramOffering;
use App\Models\AdmissionIntake;
use RuntimeException;
class FinancialAccreditationEnrichmentService {
 public function promote(ImportRow $row): object {
  if($row->status!=='candidate') throw new RuntimeException('Only candidate import rows can be promoted.');
  $p=$row->normalized_payload??$row->payload??[]; $type=$row->batch->entity_type;
  $entity=match($type){
   'fee_record'=>$this->fee($p,$row),
   'scholarship'=>$this->scholarship($p,$row),
   'accreditation'=>$this->accreditation($p,$row),
   default=>throw new RuntimeException('Unsupported financial/accreditation entity type.')
  };
  $row->update(['status'=>'promoted','match_state'=>'enriched_canonical','canonical_subject_type'=>$entity::class,'canonical_subject_id'=>$entity->id]);
  return $entity;
 }
 private function parent(ImportRow $row): object {
  if(!$row->parent_subject_type||!$row->parent_subject_id) throw new RuntimeException('A canonical parent is required.');
  return match($row->parent_subject_type){ProgramOffering::class=>ProgramOffering::findOrFail($row->parent_subject_id),AdmissionIntake::class=>AdmissionIntake::findOrFail($row->parent_subject_id),default=>throw new RuntimeException('Unsupported canonical parent.')};
 }
 private function fee(array $p,ImportRow $row): FeeRecord {
  $parent=$this->parent($row); $type=$parent instanceof AdmissionIntake?'admission_intake_id':'program_offering_id';
  $data=[$type=>$parent->id,'fee_type'=>$p['fee_type']??'tuition','amount_type'=>$p['amount_type']??'fixed','amount'=>$p['amount']??null,'minimum_amount'=>$p['minimum_amount']??null,'maximum_amount'=>$p['maximum_amount']??null,'currency'=>$p['currency']??null,'frequency'=>$p['frequency']??null,'status'=>$p['status']??'known','effective_from'=>$p['effective_from']??null,'effective_until'=>$p['effective_until']??null,'notes'=>$p['notes']??null];
  if(empty($data['amount'])&&empty($data['minimum_amount'])&&empty($data['maximum_amount'])&&empty($p['notes'])) throw new RuntimeException('Fee record needs a recorded amount/range or explanatory note.');
  return FeeRecord::create($data);
 }
 private function scholarship(array $p,ImportRow $row): Scholarship {
  $parent=$this->parent($row); $data=['name'=>trim((string)($p['name']??'')),'scholarship_type'=>$p['scholarship_type']??null,'eligibility_basis'=>$p['eligibility_basis']??null,'eligibility_description'=>$p['eligibility_description']??null,'benefit_type'=>$p['benefit_type']??null,'amount_min'=>$p['amount_min']??null,'amount_max'=>$p['amount_max']??null,'currency'=>$p['currency']??null,'benefit_frequency'=>$p['benefit_frequency']??null,'duration'=>$p['duration']??null,'renewal_condition'=>$p['renewal_condition']??null,'application_open'=>$p['application_open']??null,'application_close'=>$p['application_close']??null,'data_state'=>$p['data_state']??'awaiting_verification','status'=>$p['status']??'candidate','effective_from'=>$p['effective_from']??null,'effective_until'=>$p['effective_until']??null,'notes'=>$p['notes']??null];
  if($data['name']==='') throw new RuntimeException('Scholarship name is required.');
  if($parent instanceof AdmissionIntake)$data['admission_intake_id']=$parent->id; else $data['program_offering_id']=$parent->id;
  return Scholarship::create($data);
 }
 private function accreditation(array $p,ImportRow $row): Accreditation {
  $parent=$this->parent($row); $data=['name'=>trim((string)($p['name']??'')),'recognition_type'=>$p['recognition_type']??null,'authority_name'=>$p['authority_name']??null,'scope_type'=>$p['scope_type']??null,'reference_identifier'=>$p['reference_identifier']??null,'status'=>$p['status']??'under_review','effective_from'=>$p['effective_from']??null,'effective_until'=>$p['effective_until']??null,'last_verified_at'=>$p['last_verified_at']??null,'notes'=>$p['notes']??null];
  if($data['name']==='') throw new RuntimeException('Accreditation/recognition name is required.');
  if($parent instanceof ProgramOffering)$data['program_offering_id']=$parent->id; else $data['institution_id']=$parent->id;
  return Accreditation::create($data);
 }
}
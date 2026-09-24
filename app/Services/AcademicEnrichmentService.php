<?php
namespace App\Services;
use App\Models\AdmissionIntake;
use App\Models\EligibilityRule;
use App\Models\ImportRow;
use App\Models\MeritRecord;
use App\Models\ProgramOffering;
use App\Models\SelectionOutcome;
use RuntimeException;
class AcademicEnrichmentService {
 public function promote(ImportRow $row): object {
  if($row->status!=='candidate') throw new RuntimeException('Only candidate import rows can be promoted.');
  $p=$row->normalized_payload??$row->payload??[]; $type=$row->batch->entity_type;
  $entity=match($type){
   'admission_intake'=>$this->intake($p,$row),
   'eligibility_rule'=>$this->eligibility($p,$row),
   'merit_record'=>$this->merit($p,$row),
   'selection_outcome'=>$this->selection($p,$row),
   default=>throw new RuntimeException('Unsupported academic enrichment entity type.')
  };
  $row->update(['status'=>'promoted','match_state'=>'enriched_canonical','canonical_subject_type'=>$entity::class,'canonical_subject_id'=>$entity->id]);
  return $entity;
 }
 private function parent(ImportRow $row,string $type): object {
  if($row->parent_subject_type!==$type||!$row->parent_subject_id) throw new RuntimeException('Required canonical parent is missing.');
  return $type===ProgramOffering::class?ProgramOffering::findOrFail($row->parent_subject_id):AdmissionIntake::findOrFail($row->parent_subject_id);
 }
 private function intake(array $p,ImportRow $row): AdmissionIntake {
  $o=$this->parent($row,ProgramOffering::class); $label=trim((string)($p['label']??''));
  if($label==='') throw new RuntimeException('Admission intake label is required.');
  return AdmissionIntake::firstOrCreate(['program_offering_id'=>$o->id,'label'=>$label],['status'=>$p['status']??'planned','application_open_at'=>$p['application_open_at']??null,'application_close_at'=>$p['application_close_at']??null,'test_date'=>$p['test_date']??null,'selection_date'=>$p['selection_date']??null,'enrollment_start_at'=>$p['enrollment_start_at']??null,'enrollment_end_at'=>$p['enrollment_end_at']??null,'seat_capacity'=>$p['seat_capacity']??null,'application_url'=>$p['application_url']??null,'notes'=>$p['notes']??null]);
 }
 private function eligibility(array $p,ImportRow $row): EligibilityRule {
  $i=$this->parent($row,AdmissionIntake::class); if(empty($p['requirement'])) throw new RuntimeException('Eligibility requirement is required.');
  return EligibilityRule::create(['admission_intake_id'=>$i->id,'rule_type'=>$p['rule_type']??'general','applicant_category'=>$p['applicant_category']??null,'qualification'=>$p['qualification']??null,'requirement'=>$p['requirement'],'minimum_marks'=>$p['minimum_marks']??null,'minimum_marks_type'=>$p['minimum_marks_type']??null,'required_subject'=>$p['required_subject']??null,'entry_test_required'=>$p['entry_test_required']??false,'domicile_requirement'=>$p['domicile_requirement']??null,'minimum_age'=>$p['minimum_age']??null,'maximum_age'=>$p['maximum_age']??null,'experience_requirement'=>$p['experience_requirement']??null,'status'=>$p['status']??'active']);
 }
 private function merit(array $p,ImportRow $row): MeritRecord {
  $i=$this->parent($row,AdmissionIntake::class); if(empty($p['merit_type'])) throw new RuntimeException('Merit type is required.');
  return MeritRecord::create(['admission_intake_id'=>$i->id,'stage'=>$p['stage']??null,'merit_type'=>$p['merit_type'],'applicant_category'=>$p['applicant_category']??null,'campus_context'=>$p['campus_context']??null,'value'=>$p['value']??null,'score'=>$p['score']??null,'position'=>$p['position']??null,'unit'=>$p['unit']??null,'methodology'=>$p['methodology']??null,'list_label'=>$p['list_label']??null,'list_number'=>$p['list_number']??null,'status'=>$p['status']??'historical','effective_date'=>$p['effective_date']??null]);
 }
 private function selection(array $p,ImportRow $row): SelectionOutcome {
  $i=$this->parent($row,AdmissionIntake::class); if(empty($p['outcome_type'])) throw new RuntimeException('Selection outcome type is required.');
  return SelectionOutcome::create(['admission_intake_id'=>$i->id,'outcome_type'=>$p['outcome_type'],'applicant_category'=>$p['applicant_category']??null,'status'=>$p['status']??'recorded','threshold_value'=>$p['threshold_value']??null,'threshold_score'=>$p['threshold_score']??null,'unit'=>$p['unit']??null,'list_number'=>$p['list_number']??null,'notes'=>$p['notes']??null,'effective_date'=>$p['effective_date']??null]);
 }
}
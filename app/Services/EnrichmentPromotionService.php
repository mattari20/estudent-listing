<?php
namespace App\Services;
use App\Models\ImportRow;
use RuntimeException;
class EnrichmentPromotionService {
 public function promote(ImportRow $row): object {
  $type=$row->batch->entity_type;
  return match($type){
   'campus','program','program_offering'=>app(StructuredEnrichmentService::class)->promote($row),
   'admission_intake','eligibility_rule','merit_record','selection_outcome'=>app(AcademicEnrichmentService::class)->promote($row),
   'fee_record','scholarship','accreditation'=>app(FinancialAccreditationEnrichmentService::class)->promote($row),
   default=>throw new RuntimeException("Unsupported enrichment entity type: {$type}")
  };
 }
 public function validateParent(ImportRow $row): void {
  $type=$row->batch->entity_type;
  if(in_array($type,['campus','program'],true) && $row->parent_subject_type!==\App\Models\Institution::class) throw new RuntimeException('Campus/program imports require an Institution parent.');
  if($type==='program_offering' && $row->parent_subject_type!==\App\Models\Program::class) throw new RuntimeException('Program offering imports require a Program parent.');
  if(in_array($type,['admission_intake'],true) && $row->parent_subject_type!==\App\Models\ProgramOffering::class) throw new RuntimeException('Admission intake imports require a Program Offering parent.');
  if(in_array($type,['eligibility_rule','merit_record','selection_outcome'],true) && $row->parent_subject_type!==\App\Models\AdmissionIntake::class) throw new RuntimeException("{$type} imports require an Admission Intake parent.");
  if(in_array($type,['fee_record','scholarship'],true) && !in_array($row->parent_subject_type,[\App\Models\ProgramOffering::class,\App\Models\AdmissionIntake::class],true)) throw new RuntimeException("{$type} imports require a Program Offering or Admission Intake parent.");
  if($type==='accreditation' && !in_array($row->parent_subject_type,[\App\Models\Institution::class,\App\Models\ProgramOffering::class],true)) throw new RuntimeException('Accreditation imports require an Institution or Program Offering parent.');
 }
}
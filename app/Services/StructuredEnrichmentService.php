<?php
namespace App\Services;
use App\Models\Campus;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use RuntimeException;
class StructuredEnrichmentService {
 public function promote(ImportRow $row): object {
  if($row->status!=='candidate') throw new RuntimeException('Only candidate import rows can be promoted.');
  $p=$row->normalized_payload??$row->payload??[];
  $type=$row->batch->entity_type; $parent=$this->parent($row);
  $entity=match($type){'campus'=>$this->campus($p,$parent),'program'=>$this->program($p,$parent),'program_offering'=>$this->offering($p,$parent),default=>throw new RuntimeException('Unsupported enrichment entity type.')};
  $row->update(['status'=>'promoted','match_state'=>'enriched_canonical','canonical_subject_type'=>$entity::class,'canonical_subject_id'=>$entity->id]);
  return $entity;
 }
 private function parent(ImportRow $row): Institution|Program {
  if(!$row->parent_subject_type||!$row->parent_subject_id) throw new RuntimeException('A canonical parent is required.');
  return $row->parent_subject_type===Institution::class?Institution::findOrFail($row->parent_subject_id):Program::findOrFail($row->parent_subject_id);
 }
 private function campus(array $p,Institution $i): Campus {
  $name=trim((string)($p['name']??'')); if($name==='') throw new RuntimeException('Campus name is required.');
  return Campus::firstOrCreate(['institution_id'=>$i->id,'slug'=>$p['slug']??str()->slug($name)],['name'=>$name,'campus_type'=>$p['campus_type']??null,'status'=>$p['status']??'active','geography_id'=>$p['geography_id']??null,'address'=>$p['address']??null,'postal_code'=>$p['postal_code']??null,'latitude'=>$p['latitude']??null,'longitude'=>$p['longitude']??null]);
 }
 private function program(array $p,Institution $i): Program {
  $name=trim((string)($p['name']??'')); if($name==='') throw new RuntimeException('Program name is required.');
  return Program::firstOrCreate(['institution_id'=>$i->id,'slug'=>$p['slug']??str()->slug($name)],['name'=>$name,'program_level'=>$p['program_level']??null,'award'=>$p['award']??null,'discipline'=>$p['discipline']??null,'status'=>$p['status']??'active','description'=>$p['description']??null]);
 }
 private function offering(array $p,Program $program): ProgramOffering {
  return ProgramOffering::firstOrCreate(['program_id'=>$program->id,'campus_id'=>$p['campus_id']??null,'delivery_mode'=>$p['delivery_mode']??null,'intake_label'=>$p['intake_label']??null],['status'=>$p['status']??'active','duration'=>$p['duration']??null,'availability'=>$p['availability']??null,'effective_from'=>$p['effective_from']??null,'effective_until'=>$p['effective_until']??null]);
 }
}
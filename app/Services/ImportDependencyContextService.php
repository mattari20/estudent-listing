<?php
namespace App\Services;
use App\Models\ImportRow;
use App\Models\AdmissionIntake;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Models\Institution;
use App\Models\Campus;
class ImportDependencyContextService {
 public function build(ImportRow $row): array {
  $row->load('batch');
  $context=['parent'=>null,'chain'=>[]];
  $type=$row->parent_subject_type; $id=$row->parent_subject_id;
  if(!$type||!$id)return $context;
  $parent=$type::find($id); if(!$parent)return $context;
  $context['parent']=$parent;
  if($parent instanceof ProgramOffering){$program=$parent->program;$campus=$parent->campus;$institution=$program?->institution;$context['chain']=array_values(array_filter([$institution,$campus,$program,$parent]));}
  elseif($parent instanceof Program){$institution=$parent->institution;$context['chain']=array_values(array_filter([$institution,$parent]));}
  elseif($parent instanceof Campus){$institution=$parent->institution;$context['chain']=array_values(array_filter([$institution,$parent]));}
  elseif($parent instanceof AdmissionIntake){$offering=$parent->programOffering;$program=$offering?->program;$institution=$program?->institution;$context['chain']=array_values(array_filter([$institution,$program,$offering,$parent]));}
  elseif($parent instanceof Institution){$context['chain']=[$parent];}
  return $context;
 }
}
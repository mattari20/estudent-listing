<?php
namespace App\Services;
use App\Models\Geography;
class GeographyIntegrityService {
 public function validate(): array {
  $errors=[];
  foreach(Geography::where('status','active')->get() as $g){
   if($g->type==='country' && $g->parent_id!==null)$errors[]='country_has_parent:'.$g->id;
   if($g->type!=='country' && $g->parent_id===null)$errors[]='non_country_without_parent:'.$g->id;
   if($g->id===$g->parent_id)$errors[]='self_parent:'.$g->id;
  }
  return ['valid'=>count($errors)===0,'errors'=>$errors];
 }
}
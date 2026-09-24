<?php
namespace App\Services;
use App\Models\ImportConflict;
use App\Models\ImportRow;
use App\Models\Institution;
class ImportCrossSourceComparisonService {
 public function compare(ImportRow $row): int {
  if($row->batch->entity_type!=='institution' || !$row->canonical_subject_id) return 0;
  $institution=Institution::find($row->canonical_subject_id); if(!$institution) return 0;
  $payload=$row->normalized_payload ?? $row->payload ?? []; $count=0;
  foreach(['name','official_name','short_name','institution_type','status'] as $field){
   if(!array_key_exists($field,$payload) || $payload[$field]===null || $payload[$field]==='') continue;
   $incoming=trim((string)$payload[$field]); $current=trim((string)$institution->{$field});
   if($incoming!==$current && !ImportConflict::where('import_row_id',$row->id)->where('conflict_type','cross_source_attribute_difference')->where('status','open')->where('details->field',$field)->exists()){
    ImportConflict::create(['import_row_id'=>$row->id,'import_batch_id'=>$row->batch->id,'conflict_type'=>'cross_source_attribute_difference','severity'=>in_array($field,['name','status'],true)?'high':'warning','status'=>'open','description'=>'Imported source differs from the current canonical attribute; operator review is required.','details'=>['field'=>$field,'incoming'=>$incoming,'canonical'=>$current],'related_subject_type'=>Institution::class,'related_subject_id'=>$institution->id]);
    $row->update(['match_state'=>'conflict']); $count++;
   }
  }
  return $count;
 }
}
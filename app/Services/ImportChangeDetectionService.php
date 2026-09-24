<?php
namespace App\Services;
use App\Models\ChangeEvent;
use App\Models\ImportRow;
use App\Models\Institution;
class ImportChangeDetectionService {
 public function detect(ImportRow $row): int {
  if($row->batch->entity_type!=='institution' || !$row->canonical_subject_id) return 0;
  $institution=Institution::find($row->canonical_subject_id); if(!$institution) return 0;
  $payload=$row->normalized_payload ?? $row->payload ?? []; $count=0;
  foreach(['name','official_name','short_name','institution_type','status'] as $field){
   if(!array_key_exists($field,$payload) || $payload[$field]===null || $payload[$field]==='') continue;
   $incoming=trim((string)$payload[$field]); $current=trim((string)$institution->{$field});
   if($incoming!==$current){
    $summary=$field.': '.$current.' -> '.$incoming;
    if(!ChangeEvent::where('subject_type',Institution::class)->where('subject_id',$institution->id)->where('event_type','import_field_change')->where('summary',$summary)->exists()){
     ChangeEvent::create(['subject_type'=>Institution::class,'subject_id'=>$institution->id,'event_type'=>'import_field_change','materiality'=>in_array($field,['name','status'],true)?'high':'medium','actor_type'=>'system','status'=>'detected','summary'=>$summary,'detected_at'=>now()]); $count++;
    }
   }
  }
  return $count;
 }
}
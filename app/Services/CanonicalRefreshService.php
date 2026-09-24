<?php
namespace App\Services;
use App\Models\ChangeEvent;
use App\Models\DataReverificationQueue;
use App\Models\Institution;
use Illuminate\Support\Facades\DB;
use RuntimeException;
class CanonicalRefreshService {
 public function refreshInstitution(DataReverificationQueue $item,int $actorId,array $attributes,string $reason): Institution {
  return DB::transaction(function() use($item,$actorId,$attributes,$reason){
   $item=DataReverificationQueue::lockForUpdate()->findOrFail($item->id);
   if($item->status!=='in_review') throw new RuntimeException('Reverification item must be in review before canonical refresh.');
   if($item->subject_type!==Institution::class) throw new RuntimeException('This refresh operation supports institution subjects only.');
   $institution=Institution::lockForUpdate()->findOrFail($item->subject_id);
   $allowed=['name','official_name','short_name','institution_type','status'];
   $changes=[]; foreach($allowed as $field){ if(array_key_exists($field,$attributes) && (string)$institution->{$field}!==(string)$attributes[$field]) $changes[$field]=['old'=>$institution->{$field},'new'=>$attributes[$field]]; }
   if(!$changes) throw new RuntimeException('No canonical changes were supplied.');
   $institution->update(array_intersect_key($attributes,array_flip($allowed)));
   foreach($changes as $field=>$change) ChangeEvent::create(['subject_type'=>Institution::class,'subject_id'=>$institution->id,'event_type'=>'reverification_canonical_refresh','materiality'=>in_array($field,['name','status'],true)?'high':'medium','actor_type'=>'user','actor_id'=>$actorId,'status'=>'applied','summary'=>$field.': '.$change['old'].' -> '.$change['new'],'detected_at'=>now()]);
   return $institution;
  });
 }
}
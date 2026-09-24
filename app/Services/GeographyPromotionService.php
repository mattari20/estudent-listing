<?php
namespace App\Services;
use App\Models\Geography;
use App\Models\ImportRow;
use Illuminate\Support\Facades\DB;
class GeographyPromotionService {
 public function promote(ImportRow $row): Geography {
  $payload=$row->normalized_payload??$row->payload??[];
  $type=(string)($payload['type']??''); $parentSlug=(string)($payload['parent_slug']??''); $parentType=(string)($payload['parent_type']??'');
  $parent=$type==='country'?null:Geography::where('slug',$parentSlug)->where('type',$parentType)->where('status','active')->firstOrFail();
  return DB::transaction(function()use($payload,$type,$parent,$row){
   $geo=Geography::updateOrCreate(['slug'=>$payload['slug'],'type'=>$type],['parent_id'=>$parent?->id,'name'=>$payload['name'],'code'=>$payload['code']??null,'status'=>$payload['status']??'active']);
   $row->update(['status'=>'promoted','canonical_subject_type'=>Geography::class,'canonical_subject_id'=>$geo->id,'match_state'=>'matched']);
   return $geo;
  });
 }
}
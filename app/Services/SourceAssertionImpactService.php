<?php
namespace App\Services;
use App\Models\Assertion;
use App\Models\SourceChangeAssertionLink;
use App\Models\SourceChangeObservation;
class SourceAssertionImpactService {
 public function linkAffectedAssertions(SourceChangeObservation $observation): int {
  if($observation->change_state!=='changed') return 0;
  $count=0; $classifier=app(SourceImpactClassifier::class);
  Assertion::query()->whereHas('evidence',fn($q)=>$q->where('source_id',$observation->source_id))->chunkById(250,function($assertions)use($observation,$classifier,&$count){
   foreach($assertions as $assertion){
    SourceChangeAssertionLink::firstOrCreate(['source_change_observation_id'=>$observation->id,'assertion_id'=>$assertion->id],['attribute'=>$assertion->attribute,'impact'=>$classifier->classify($assertion->attribute),'status'=>'candidate','reason'=>'Assertion is sourced from the monitored source; content changed and requires verification.']); $count++;
   }
  });
  return $count;
 }
}
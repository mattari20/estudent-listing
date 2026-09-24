<?php
namespace App\Services;
use App\Models\SourceChangeObservation;
class SourceChangeIntelligenceService {
 public function process(SourceChangeObservation $observation): array {
  if($observation->change_state!=='changed') return ['linked'=>0,'events'=>0,'queued'=>0];
  $linked=app(SourceAssertionImpactService::class)->linkAffectedAssertions($observation);
  $events=0; $queued=0;
  $observation->load('currentSnapshot');
  foreach($observation->assertionLinks()->with('assertion.evidence')->get() as $link){
   app(SourceChangeEventProjector::class)->project($link); $events++;
   app(SourceChangeReverificationService::class)->enqueue($link); $queued++;
  }
  $observation->update(['status'=>'actionable']);
  return compact('linked','events','queued');
 }
}
<?php
namespace App\Services;
use App\Models\DataQualityEvent;
use App\Models\Evidence;
use Carbon\Carbon;
class SourceFreshnessService {
 public function state(Evidence $evidence, ?Carbon $asOf=null): string {
  $asOf=$asOf??now();
  if($evidence->expires_at && $evidence->expires_at->isPast()) return 'stale';
  if(!$evidence->observed_at) return 'unknown';
  $ageDays=$evidence->observed_at->diffInDays($asOf);
  if($ageDays<=30) return 'fresh';
  if($ageDays<=90) return 'aging';
  return 'stale';
 }
 public function recordQuality(Evidence $evidence): string {
  $state=$this->state($evidence);
  DataQualityEvent::updateOrCreate(['subject_type'=>Evidence::class,'subject_id'=>$evidence->id,'dimension'=>'freshness','state'=>$state],['severity'=>$state==='stale'?'warning':'info','description'=>'Evidence freshness state is '.$state,'observed_at'=>now()]);
  return $state;
 }
}
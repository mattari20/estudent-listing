<?php
namespace App\Services;
use App\Models\Evidence;
use App\Models\SourceMonitoringPolicy;
class SourceMonitoringService {
 public function run(?int $policyId=null): array {
  $policies=SourceMonitoringPolicy::query()->where('status','active')->when($policyId,fn($q)=>$q->whereKey($policyId))->get();
  $summary=['policies'=>0,'evidence_checked'=>0,'fresh'=>0,'aging'=>0,'stale'=>0,'unknown'=>0,'queued'=>0];
  foreach($policies as $policy){
   $summary['policies']++;
   $query=Evidence::query()->when($policy->source_id,fn($q)=>$q->where('source_id',$policy->source_id));
   if($policy->subject_type) $query->whereHas('assertions',fn($q)=>$q->where('subject_type',$policy->subject_type));
   $evidence=$query->get();
   foreach($evidence as $item){
    $summary['evidence_checked']++;
    $state=app(SourceFreshnessService::class)->stateWithThresholds($item,$policy->fresh_after_days,$policy->stale_after_days);
    if($state==='fresh')$summary['fresh']++; elseif($state==='aging')$summary['aging']++; elseif($state==='stale')$summary['stale']++; else $summary['unknown']++;
    if(in_array($state,['aging','stale'],true)){ $priority=$state==='stale'?'high':$policy->priority; app(ReverificationQueueService::class)->enqueueEvidence($item,'monitoring_'.$state,$priority); $summary['queued']++; }
   }
   $policy->update(['last_run_at'=>now()]);
  }
  return $summary;
 }
}
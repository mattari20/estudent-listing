<?php
namespace App\Services;
use App\Models\Source;
use App\Models\SourceMonitoringPolicy;
class SourceMonitoringBatchService {
 public function run(?int $policyId=null): array {
  $policies=SourceMonitoringPolicy::query()->where('status','active')->when($policyId,fn($q)=>$q->whereKey($policyId))->get();
  $summary=['policies'=>0,'sources'=>0,'completed'=>0,'failed'=>0,'changed'=>0,'queued'=>0];
  foreach($policies as $policy){ $summary['policies']++; $sources=$policy->source_id?Source::whereKey($policy->source_id)->get():Source::where('status','active')->whereNotNull('url')->get();
   $policyFailed=false;
   foreach($sources as $source){ $summary['sources']++; $result=app(SourceMonitoringPipeline::class)->run($source);
    if(($result['run']->status??'')==='completed')$summary['completed']++; else{$summary['failed']++;$policyFailed=true;continue;}
    if(($result['observation']?->change_state)==='changed'){$summary['changed']++;$summary['queued']+=($result['intelligence']['queued']??0);}
   }
   $policyFailed?app(MonitoringPolicyStateService::class)->failure($policy):app(MonitoringPolicyStateService::class)->success($policy);
   $policy->update(['last_run_at'=>now()]);
  }
  return $summary;
 }
}
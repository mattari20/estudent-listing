<?php
namespace App\Services;
class SourceMonitoringBatchService {
 public function run(?int $policyId=null): array {
  $policies=app(MonitoringDuePolicyService::class)->due($policyId);
  $summary=['policies'=>0,'sources'=>0,'completed'=>0,'failed'=>0,'changed'=>0,'queued'=>0,'skipped_not_due'=>0];
  foreach($policies as $policy){$summary['policies']++;app(MonitoringPolicyValidationService::class)->validate($policy);$sources=$policy->source_id?\App\Models\Source::whereKey($policy->source_id)->get():\App\Models\Source::where('status','active')->whereNotNull('url')->get();$failed=false;
   foreach($sources as $source){$summary['sources']++;$r=app(SourceMonitoringPipeline::class)->run($source);if(($r['run']->status??'')==='completed')$summary['completed']++;else{$summary['failed']++;$failed=true;continue;}if(($r['observation']?->change_state)==='changed'){$summary['changed']++;$summary['queued']+=($r['intelligence']['queued']??0);}}
   $failed?app(MonitoringPolicyStateService::class)->failure($policy):app(MonitoringPolicyStateService::class)->success($policy);$policy->update(['last_run_at'=>now()]);
  } return $summary;
 }
}
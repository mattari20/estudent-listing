<?php
namespace App\Services;
use App\Models\Source;
use App\Models\SourceMonitoringPolicy;
class MonitoringDryRunService {
 public function run(?int $policyId=null): array {
  $policies=SourceMonitoringPolicy::where('status','active')->when($policyId,fn($q)=>$q->whereKey($policyId))->get(); $rows=[];
  foreach($policies as $policy){ $sources=$policy->source_id?Source::whereKey($policy->source_id)->get():Source::where('status','active')->whereNotNull('url')->get(); foreach($sources as $source)$rows[]=['policy'=>$policy->name,'source'=>$source->name,'url'=>$source->url,'would_retrieve'=>true]; }
  return ['policies'=>$policies->count(),'sources'=>count($rows),'rows'=>$rows];
 }
}
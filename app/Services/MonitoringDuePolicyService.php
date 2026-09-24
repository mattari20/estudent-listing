<?php
namespace App\Services;
use App\Models\SourceMonitoringPolicy;
class MonitoringDuePolicyService {
 public function due(?int $policyId=null) {
  return SourceMonitoringPolicy::query()->where('status','active')->when($policyId,fn($q)=>$q->whereKey($policyId))
   ->where(fn($q)=>$q->whereNull('next_run_at')->orWhere('next_run_at','<=',now()))->get();
 }
}
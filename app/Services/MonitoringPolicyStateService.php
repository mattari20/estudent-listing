<?php
namespace App\Services;
use App\Models\SourceMonitoringPolicy;
class MonitoringPolicyStateService {
 public function success(SourceMonitoringPolicy $policy): void {
  $policy->update(['last_success_at'=>now(),'consecutive_failures'=>0,'next_run_at'=>$this->nextRun($policy)]);
 }
 public function failure(SourceMonitoringPolicy $policy): void {
  $policy->update(['last_failure_at'=>now(),'consecutive_failures'=>$policy->consecutive_failures+1,'next_run_at'=>$this->nextRun($policy)]);
 }
 public function nextRun(SourceMonitoringPolicy $policy) {
  $cadence=$policy->cadence ?: 'daily';
  return match($cadence){'hourly'=>now()->addHour(),'weekly'=>now()->addWeek(),'monthly'=>now()->addMonth(),default=>now()->addDay()};
 }
}
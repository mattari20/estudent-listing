<?php
namespace App\Services;
use App\Models\SourceMonitoringPolicy;
use App\Models\SourceRetrievalRun;
class MonitoringHealthService {
 public function summary(): array {
  return ['active_policies'=>SourceMonitoringPolicy::where('status','active')->count(),'due_policies'=>SourceMonitoringPolicy::where('status','active')->where(function($q){$q->whereNull('next_run_at')->orWhere('next_run_at','<=',now());})->count(),'failed_runs'=>SourceRetrievalRun::where('status','failed')->where('created_at','>=',now()->subDay())->count()];
 }
}
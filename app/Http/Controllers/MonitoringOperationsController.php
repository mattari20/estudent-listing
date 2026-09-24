<?php
namespace App\Http\Controllers;
use App\Models\MonitoringAlert;
use App\Models\SourceChangeObservation;
use App\Models\SourceMonitoringPolicy;
use App\Models\SourceRetrievalRun;
use App\Services\MonitoringAlertStateService;
use Illuminate\Http\Request;
class MonitoringOperationsController {
 public function dashboard() {
  $health=app(\App\Services\MonitoringHealthService::class)->summary();
  $alerts=MonitoringAlert::whereIn('status',['open','acknowledged'])->latest('last_detected_at')->limit(30)->get();
  $policies=SourceMonitoringPolicy::where('status','active')->orderBy('next_run_at')->limit(30)->get();
  return view('institutes.operations.monitoring',compact('health','alerts','policies'));
 }
 public function acknowledge(MonitoringAlert $alert, Request $request) {
  app(MonitoringAlertStateService::class)->acknowledge($alert);
  app(\App\Services\MonitoringAuditService::class)->record('alert_acknowledged','Monitoring alert acknowledged',$alert->id,'info');
  return back()->with('status','Alert acknowledged.');
 }
 public function resolve(MonitoringAlert $alert, Request $request) {
  app(MonitoringAlertStateService::class)->resolve($alert);
  app(\App\Services\MonitoringAuditService::class)->record('alert_resolved','Monitoring alert resolved',$alert->id,'info');
  return back()->with('status','Alert resolved.');
 }
 public function history() {
  $runs=SourceRetrievalRun::with('source')->latest('started_at')->paginate(30);
  $changes=SourceChangeObservation::with('source')->latest('observed_at')->paginate(30,['*'],'changes_page');
  return view('institutes.operations.monitoring-history',compact('runs','changes'));
 }
}
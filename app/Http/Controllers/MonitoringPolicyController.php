<?php
namespace App\Http\Controllers;
use App\Models\Source;
use App\Models\SourceMonitoringPolicy;
use App\Services\MonitoringPolicyValidationService;
use Illuminate\Http\Request;
class MonitoringPolicyController {
 public function create(){ $sources=Source::where('status','active')->whereNotNull('url')->orderBy('name')->get(); return view('institutes.operations.monitoring-policy-form',compact('sources')); }
 public function store(Request $request, MonitoringPolicyValidationService $validator){
  $data=$request->validate(['name'=>'required|string|max:255','source_id'=>'nullable|integer|exists:sources,id','subject_type'=>'nullable|string|max:255','attribute'=>'nullable|string|max:255','fresh_after_days'=>'required|integer|min:0','stale_after_days'=>'required|integer|min:1','priority'=>'required|in:low,normal,high,urgent','cadence'=>'required|in:hourly,daily,weekly,monthly','status'=>'required|in:active,paused,draft']);
  if($data['stale_after_days']<=$data['fresh_after_days']) return back()->withErrors(['stale_after_days'=>'Stale threshold must exceed fresh threshold.'])->withInput();
  $policy=SourceMonitoringPolicy::create($data); if($policy->status==='active')$policy->update(['next_run_at'=>now()]);
  return redirect()->route('institutes.operations.monitoring.dashboard')->with('status','Monitoring policy created.');
 }
 public function toggle(SourceMonitoringPolicy $policy){$policy->update(['status'=>$policy->status==='active'?'paused':'active','next_run_at'=>$policy->status==='active'?now():null]);return back()->with('status','Monitoring policy status updated.');}
}
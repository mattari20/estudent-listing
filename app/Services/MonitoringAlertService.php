<?php
namespace App\Services;
use App\Models\MonitoringAlert;
use App\Models\Source;
use App\Models\SourceMonitoringPolicy;
class MonitoringAlertService {
 public function raise(string $type,string $severity,string $message,?Source $source=null,?SourceMonitoringPolicy $policy=null,array $details=[]): MonitoringAlert {
  $key=sha1($type.'|'.($source?->id??0).'|'.($policy?->id??0));
  $alert=MonitoringAlert::where('dedupe_key',$key)->where('status','open')->first();
  if($alert){$alert->update(['last_detected_at'=>now(),'severity'=>$severity,'message'=>$message,'details'=>$details]);return $alert;}
  return MonitoringAlert::create(['source_monitoring_policy_id'=>$policy?->id,'source_id'=>$source?->id,'alert_type'=>$type,'severity'=>$severity,'status'=>'open','dedupe_key'=>$key,'message'=>$message,'details'=>$details,'first_detected_at'=>now(),'last_detected_at'=>now()]);
 }
}
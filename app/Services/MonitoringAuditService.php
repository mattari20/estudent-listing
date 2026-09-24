<?php
namespace App\Services;
use App\Models\SecurityAuditEvent;
class MonitoringAuditService {
 public function record(string $event,string $description,?int $subjectId=null,string $severity='info',array $metadata=[]): SecurityAuditEvent {
  return SecurityAuditEvent::create(['event_type'=>'monitoring_'.$event,'subject_type'=>'monitoring','subject_id'=>$subjectId,'severity'=>$severity,'description'=>$description.' '.json_encode($metadata)]);
 }
}
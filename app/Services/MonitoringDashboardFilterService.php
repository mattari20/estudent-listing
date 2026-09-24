<?php
namespace App\Services;
use App\Models\MonitoringAlert;
class MonitoringDashboardFilterService {
 public function alerts(?string $severity=null,?string $status=null) { return MonitoringAlert::query()->when($severity,fn($q)=>$q->where('severity',$severity))->when($status,fn($q)=>$q->where('status',$status))->latest('last_detected_at')->limit(50)->get(); }
}
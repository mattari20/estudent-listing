<?php
namespace App\Services;
use App\Models\MonitoringAlert;
class MonitoringAlertStateService {
 public function acknowledge(MonitoringAlert $alert): MonitoringAlert { $alert->update(['status'=>'acknowledged','acknowledged_at'=>now()]); return $alert->refresh(); }
 public function resolve(MonitoringAlert $alert): MonitoringAlert { $alert->update(['status'=>'resolved','resolved_at'=>now()]); return $alert->refresh(); }
}
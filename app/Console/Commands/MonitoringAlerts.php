<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class MonitoringAlerts extends Command {
 protected $signature='institutes:monitor-alerts';
 protected $description='Show open monitoring alerts.';
 public function handle(): int {
  $rows=\App\Models\MonitoringAlert::whereIn('status',['open','acknowledged'])->latest('last_detected_at')->limit(100)->get(['id','alert_type','severity','status','message','last_detected_at']);
  $this->table(['ID','Type','Severity','Status','Message','Last Detected'],$rows->map(fn($r)=>[$r->id,$r->alert_type,$r->severity,$r->status,$r->message,$r->last_detected_at])->all()); return self::SUCCESS;
 }
}
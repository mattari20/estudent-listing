<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class MonitoringHealth extends Command {
 protected $signature='institutes:monitor-health';
 protected $description='Show operational health summary for institute source monitoring.';
 public function handle(): int { $this->table(['Metric','Value'],collect(app(\App\Services\MonitoringHealthService::class)->summary())->map(fn($v,$k)=>[$k,$v])->values()->all()); return self::SUCCESS; }
}
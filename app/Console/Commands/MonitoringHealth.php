<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class MonitoringHealth extends Command {
 protected $signature='institutes:monitor-health {--json : Output JSON}';
 protected $description='Show operational health summary for institute source monitoring.';
 public function handle(): int {$s=app(\App\Services\MonitoringHealthService::class)->summary();if($this->option('json')){$this->line(json_encode($s));return self::SUCCESS;}$this->table(['Metric','Value'],collect($s)->map(fn($v,$k)=>[$k,$v])->values()->all());return self::SUCCESS;}
}
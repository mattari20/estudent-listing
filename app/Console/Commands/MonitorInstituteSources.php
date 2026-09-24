<?php
namespace App\Console\Commands;
use App\Services\SourceMonitoringService;
use Illuminate\Console\Command;
class MonitorInstituteSources extends Command {
 protected $signature='institutes:monitor-sources {--policy= : Run one monitoring policy by ID}';
 protected $description='Evaluate governed source freshness policies and queue reverification work.';
 public function handle(SourceMonitoringService $monitor): int {
  $summary=$monitor->run($this->option('policy')?(int)$this->option('policy'):null);
  $this->table(['Metric','Count'],collect($summary)->map(fn($v,$k)=>[$k,$v])->values()->all());
  return self::SUCCESS;
 }
}
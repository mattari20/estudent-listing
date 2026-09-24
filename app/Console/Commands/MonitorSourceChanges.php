<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class MonitorSourceChanges extends Command {
 protected $signature='institutes:monitor-changes {--policy= : Optional monitoring policy ID}';
 protected $description='Retrieve monitored institute sources and process detected content changes.';
 public function handle(): int {
  $summary=app(\App\Services\SourceMonitoringBatchService::class)->run($this->option('policy')?(int)$this->option('policy'):null);
  $this->table(['Metric','Value'],collect($summary)->map(fn($v,$k)=>[$k,$v])->values()->all());
  return self::SUCCESS;
 }
}
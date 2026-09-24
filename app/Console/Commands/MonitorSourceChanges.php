<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class MonitorSourceChanges extends Command {
 protected $signature='institutes:monitor-changes {--policy= : Optional monitoring policy ID} {--dry-run : Show sources without retrieving them}';
 protected $description='Retrieve monitored institute sources and process detected content changes.';
 public function handle(): int {
  if($this->option('dry-run')){$s=app(\App\Services\MonitoringDryRunService::class)->run($this->option('policy')?(int)$this->option('policy'):null);$this->table(['Metric','Value'],[['policies',$s['policies']],['sources',$s['sources']]]);return self::SUCCESS;}
  $s=app(\App\Services\SourceMonitoringBatchService::class)->run($this->option('policy')?(int)$this->option('policy'):null);$this->table(['Metric','Value'],collect($s)->map(fn($v,$k)=>[$k,$v])->values()->all());return self::SUCCESS;
 }
}
<?php
namespace App\Services;
use App\Models\SourceMonitoringPolicy;
class MonitoringAlertPolicyService {
 public function severityForFailures(int $failures): string { return $failures>=5?'critical':($failures>=3?'high':($failures>=1?'warning':'info')); }
 public function severityForChange(string $impact): string { return $impact==='high'?'high':($impact==='medium'?'warning':'info'); }
}
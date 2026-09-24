<?php
namespace App\Services;
use App\Models\SourceMonitoringPolicy;
class MonitoringPolicyValidationService {
 public function validate(SourceMonitoringPolicy $policy): void {
  if($policy->fresh_after_days<0 || $policy->stale_after_days<=$policy->fresh_after_days) throw new \InvalidArgumentException('Invalid freshness thresholds.');
  if($policy->priority && !in_array($policy->priority,['low','normal','high','urgent'],true)) throw new \InvalidArgumentException('Invalid monitoring priority.');
  if($policy->cadence && !in_array($policy->cadence,['hourly','daily','weekly','monthly'],true)) throw new \InvalidArgumentException('Invalid monitoring cadence.');
 }
}
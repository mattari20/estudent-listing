<?php

namespace App\Services;

use App\Models\SourceMonitoringPolicy;
use RuntimeException;

class MonitoringPolicyStateService
{
    public function success(SourceMonitoringPolicy $policy): void
    {
        if (! $policy->update([
            'last_success_at' => now(),
            'consecutive_failures' => 0,
            'next_run_at' => $this->nextRun($policy),
        ])) {
            throw new RuntimeException('Policy success state was not persisted.');
        }
    }

    public function failure(SourceMonitoringPolicy $policy): void
    {
        if (! $policy->update([
            'last_failure_at' => now(),
            'consecutive_failures' => $policy->consecutive_failures + 1,
            'next_run_at' => $this->nextRun($policy),
        ])) {
            throw new RuntimeException('Policy failure state was not persisted.');
        }
    }

    public function nextRun(SourceMonitoringPolicy $policy)
    {
        $cadence = $policy->cadence ?: 'daily';

        return match ($cadence) {
            'hourly' => now()->addHour(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default => now()->addDay(),
        };
    }
}

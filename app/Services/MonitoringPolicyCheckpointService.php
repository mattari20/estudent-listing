<?php

namespace App\Services;

use App\Models\SourceMonitoringPolicy;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MonitoringPolicyCheckpointService
{
    public function startPolicyRun(int $policyId, ?string $executionRunId): int
    {
        return (int) DB::transaction(function () use ($policyId, $executionRunId): int {
            SourceMonitoringPolicy::query()->lockForUpdate()->findOrFail($policyId);

            $cutoff = now()->subMinutes(15);
            $freshRunning = DB::table('monitoring_policy_runs')
                ->where('source_monitoring_policy_id', $policyId)
                ->where('status', 'running')
                ->where(function ($query) use ($cutoff): void {
                    $query->where('heartbeat_at', '>=', $cutoff)
                        ->orWhere(function ($fallback) use ($cutoff): void {
                            $fallback->whereNull('heartbeat_at')->where('started_at', '>=', $cutoff);
                        });
                })
                ->exists();

            if ($freshRunning) {
                throw new RuntimeException('A monitoring policy run is already active.');
            }

            // Only attempts with no heartbeat for at least 15 minutes are
            // classified as interrupted. The policy-row lock serializes direct
            // callers as well as the scheduled command path.
            DB::table('monitoring_policy_runs')
                ->where('source_monitoring_policy_id', $policyId)
                ->where('status', 'running')
                ->where(function ($query) use ($cutoff): void {
                    $query->where('heartbeat_at', '<', $cutoff)
                        ->orWhere(function ($fallback) use ($cutoff): void {
                            $fallback->whereNull('heartbeat_at')->where('started_at', '<', $cutoff);
                        });
                })
                ->update([
                    'status' => 'interrupted',
                    'failure_code' => 'superseded_by_new_attempt',
                    'finished_at' => now(),
                    'updated_at' => now(),
                ]);

            return (int) DB::table('monitoring_policy_runs')->insertGetId([
                'source_monitoring_policy_id' => $policyId,
                'execution_run_id' => $executionRunId,
                'status' => 'running',
                'started_at' => now(),
                'heartbeat_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function heartbeatPolicyRun(int $policyRunId): void
    {
        DB::table('monitoring_policy_runs')
            ->where('id', $policyRunId)
            ->where('status', 'running')
            ->update([
                'heartbeat_at' => now(),
                'updated_at' => now(),
            ]);

        if (! DB::table('monitoring_policy_runs')
            ->where('id', $policyRunId)
            ->where('status', 'running')
            ->exists()) {
            throw new RuntimeException('Monitoring policy run heartbeat was not persisted.');
        }
    }

    public function failPolicyRun(int $policyRunId, string $failureCode): void
    {
        $updated = DB::table('monitoring_policy_runs')
            ->where('id', $policyRunId)
            ->where('status', 'running')
            ->update([
                'status' => 'failed',
                'failure_code' => $failureCode,
                'finished_at' => now(),
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            throw new RuntimeException('Policy run failure checkpoint was not persisted.');
        }
    }

    public function startSource(int $policyRunId, int $sourceId): void
    {
        $persisted = DB::table('monitoring_policy_run_sources')->updateOrInsert(
            ['monitoring_policy_run_id' => $policyRunId, 'source_id' => $sourceId],
            [
                'status' => 'running',
                'started_at' => now(),
                'finished_at' => null,
                'failure_code' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        if (! $persisted) {
            throw new RuntimeException('Source start checkpoint was not persisted.');
        }
    }

    public function failSource(int $policyRunId, int $sourceId, string $failureCode, ?int $retrievalRunId = null): void
    {
        $updated = DB::table('monitoring_policy_run_sources')
            ->where('monitoring_policy_run_id', $policyRunId)
            ->where('source_id', $sourceId)
            ->where('status', 'running')
            ->update([
                'status' => 'failed',
                'failure_code' => $failureCode,
                'retrieval_run_id' => $retrievalRunId,
                'finished_at' => now(),
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            throw new RuntimeException('Source failure checkpoint was not persisted.');
        }
    }

    public function completeSource(int $policyRunId, int $sourceId, ?int $retrievalRunId, ?int $observationId): void
    {
        $updated = DB::table('monitoring_policy_run_sources')
            ->where('monitoring_policy_run_id', $policyRunId)
            ->where('source_id', $sourceId)
            ->where('status', 'running')
            ->update([
                'status' => 'completed',
                'retrieval_run_id' => $retrievalRunId,
                'observation_id' => $observationId,
                'finished_at' => now(),
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            throw new RuntimeException('Source completion checkpoint was not persisted.');
        }
    }

    public function finalizePolicy(
        SourceMonitoringPolicy $policy,
        int $policyRunId,
        bool $failed,
        array $summary
    ): void {
        DB::transaction(function () use ($policy, $policyRunId, $failed, $summary): void {
            $state = app(MonitoringPolicyStateService::class);

            if ($failed) {
                $state->failure($policy);
            } else {
                $state->success($policy);
            }

            if (! $policy->update(['last_run_at' => now()])) {
                throw new RuntimeException('Policy last-run timestamp was not persisted.');
            }

            $updated = DB::table('monitoring_policy_runs')
                ->where('id', $policyRunId)
                ->where('status', 'running')
                ->update([
                    'status' => $failed ? 'failed' : 'completed',
                    'summary' => json_encode($summary, JSON_THROW_ON_ERROR),
                    'finished_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new RuntimeException('Policy run finalization checkpoint was not persisted.');
            }
        });
    }
}

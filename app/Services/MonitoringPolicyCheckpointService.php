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
            // Retain historical attempts, but only supersede them atomically with
            // creation of the replacement attempt. An insert failure rolls this
            // classification back and leaves the old attempt unchanged.
            DB::table('monitoring_policy_runs')
                ->where('source_monitoring_policy_id', $policyId)
                ->where('status', 'running')
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
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
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

            $policy->refresh();
            $statePersisted = $failed
                ? $policy->last_failure_at !== null
                : $policy->last_success_at !== null && (int) $policy->consecutive_failures === 0;

            if (! $statePersisted) {
                throw new RuntimeException('Policy state was not persisted.');
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

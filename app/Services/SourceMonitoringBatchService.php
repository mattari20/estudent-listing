<?php

namespace App\Services;

use App\Models\Source;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SourceMonitoringBatchService
{
    public function run(?int $policyId = null, ?Closure $heartbeat = null, ?string $executionRunId = null): array
    {
        $policies = app(MonitoringDuePolicyService::class)->due($policyId);
        $summary = [
            'policies' => 0,
            'sources' => 0,
            'completed' => 0,
            'failed' => 0,
            'changed' => 0,
            'queued' => 0,
            'discovery_batches' => 0,
            'outcomes_recorded' => 0,
            'discovery_candidates' => 0,
            'discovery_duplicates' => 0,
        ];

        foreach ($policies as $policy) {
            $heartbeat?->__invoke();
            $summary['policies']++;

            // A previous run left in 'running' state has no successful final checkpoint.
            // Mark it interrupted before opening a new attempt; never rewrite its history.
            DB::table('monitoring_policy_runs')
                ->where('source_monitoring_policy_id', $policy->id)
                ->where('status', 'running')
                ->update([
                    'status' => 'interrupted',
                    'failure_code' => 'superseded_by_new_attempt',
                    'finished_at' => now(),
                    'updated_at' => now(),
                ]);

            $policyRunId = DB::table('monitoring_policy_runs')->insertGetId([
                'source_monitoring_policy_id' => $policy->id,
                'execution_run_id' => $executionRunId,
                'status' => 'running',
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            app(MonitoringPolicyValidationService::class)->validate($policy);

            $sources = $policy->source_id
                ? Source::whereKey($policy->source_id)->get()
                : Source::where('status', 'active')->whereNotNull('url')->get();

            $failed = false;

            foreach ($sources as $source) {
                $heartbeat?->__invoke();
                $summary['sources']++;
                DB::table('monitoring_policy_run_sources')->updateOrInsert(
                    ['monitoring_policy_run_id' => $policyRunId, 'source_id' => $source->id],
                    ['status' => 'running', 'started_at' => now(), 'finished_at' => null, 'failure_code' => null, 'updated_at' => now(), 'created_at' => now()]
                );

                try {
                    $result = app(SourceMonitoringPipeline::class)->run($source);
                } catch (Throwable $exception) {
                    // A thrown retrieval/parser/intelligence exception must be treated
                    // as a failed source attempt, not allowed to bypass policy state.
                    // Keep exception messages out of alerts/audits because they may
                    // contain response fragments, credentials or provider details.
                    $summary['failed']++;
                    $failed = true;
                    $exceptionClass = mb_substr(get_class($exception), 0, 160);

                    DB::table('monitoring_policy_run_sources')
                        ->where('monitoring_policy_run_id', $policyRunId)
                        ->where('source_id', $source->id)
                        ->update(['status' => 'failed', 'failure_code' => $exceptionClass, 'finished_at' => now(), 'updated_at' => now()]);
                    app(MonitoringAlertService::class)->raise(
                        'retrieval_failure',
                        app(MonitoringAlertPolicyService::class)->severityForFailures($policy->consecutive_failures + 1),
                        'Source monitoring pipeline threw '.$exceptionClass.'.',
                        $source,
                        $policy,
                        ['exception_class' => $exceptionClass]
                    );
                    app(MonitoringAuditService::class)->record(
                        'retrieval_failure',
                        'Source monitoring pipeline threw an exception',
                        $source->id,
                        'warning',
                        ['exception_class' => $exceptionClass, 'policy_id' => $policy->id]
                    );

                    continue;
                }

                if (($result['run']->status ?? '') !== 'completed') {
                    $summary['failed']++;
                    $failed = true;
                    DB::table('monitoring_policy_run_sources')
                        ->where('monitoring_policy_run_id', $policyRunId)
                        ->where('source_id', $source->id)
                        ->update(['status' => 'failed', 'failure_code' => 'retrieval_not_completed', 'retrieval_run_id' => $result['run']->id ?? null, 'finished_at' => now(), 'updated_at' => now()]);
                    app(MonitoringAlertService::class)->raise(
                        'retrieval_failure',
                        app(MonitoringAlertPolicyService::class)->severityForFailures($policy->consecutive_failures + 1),
                        'Source retrieval failed: '.($result['run']->error_message ?? 'unknown error'),
                        $source,
                        $policy
                    );
                    $runId = $result['run']->id ?? null;
                    app(MonitoringAuditService::class)->record(
                        'retrieval_failure',
                        'Source retrieval failed',
                        $source->id,
                        'warning',
                        ['retrieval_run_id' => $runId],
                        $runId !== null ? 'monitoring:retrieval_failure:'.$runId : null
                    );
                    continue;
                }

                $summary['completed']++;

                $observation = $result['observation'] ?? null;

                if ($observation?->change_state === 'changed') {
                    $summary['changed']++;
                    $summary['queued'] += ($result['intelligence']['queued'] ?? 0);

                    $impact = $observation->materiality ?? 'unknown';
                    app(MonitoringAlertService::class)->raise(
                        'source_change',
                        app(MonitoringAlertPolicyService::class)->severityForChange($impact),
                        'Monitored source content changed.',
                        $source,
                        $policy,
                        ['observation_id' => $observation->id]
                    );
                    app(MonitoringAuditService::class)->record(
                        'source_change',
                        'Monitored source content changed',
                        $source->id,
                        'info',
                        ['observation_id' => $observation->id],
                        'monitoring:source_change:observation:'.$observation->id
                    );
                }

                // Record every successful retrieval observation, including unchanged
                // and baseline snapshots. Otherwise healthy no-change runs leave the
                // outcome ledger empty and live scheduler acceptance cannot be proven.
                if ($observation !== null) {
                    $discovery = app(SourceChangeDiscoveryService::class)->process($observation);

                    if (is_array($discovery) && isset($discovery['outcome_id'])) {
                        $summary['outcomes_recorded']++;
                    }

                    if ($observation->change_state === 'changed' && is_array($discovery)) {
                        $summary['discovery_batches']++;
                        $summary['discovery_candidates'] += (int) ($discovery['candidate_rows'] ?? 0);
                        $summary['discovery_duplicates'] += (int) ($discovery['duplicate_rows'] ?? 0);
                    }
                }

                // This is the durable source completion checkpoint. If the process
                // dies before this write, the next attempt replays the source through
                // existing retrieval/observation/outcome idempotency boundaries.
                DB::table('monitoring_policy_run_sources')
                    ->where('monitoring_policy_run_id', $policyRunId)
                    ->where('source_id', $source->id)
                    ->update([
                        'status' => 'completed',
                        'retrieval_run_id' => $result['run']->id ?? null,
                        'observation_id' => $observation?->id,
                        'finished_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            $failed
                ? app(MonitoringPolicyStateService::class)->failure($policy)
                : app(MonitoringPolicyStateService::class)->success($policy);

            $policy->update(['last_run_at' => now()]);

            DB::table('monitoring_policy_runs')
                ->where('id', $policyRunId)
                ->update([
                    'status' => $failed ? 'failed' : 'completed',
                    'summary' => json_encode($summary, JSON_THROW_ON_ERROR),
                    'finished_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return $summary;
    }
}

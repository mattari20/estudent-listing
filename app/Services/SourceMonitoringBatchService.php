<?php

namespace App\\Services;

use App\\Models\\Source;
use Closure;
use Throwable;

class SourceMonitoringBatchService
{
    public function run(?int $policyId = null, ?Closure $heartbeat = null, ?string $executionRunId = null): array
    {
        $policies = app(MonitoringDuePolicyService::class)->due($policyId);
        $checkpoints = app(MonitoringPolicyCheckpointService::class);
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
            // A failed heartbeat is an infrastructure/ownership failure. It is
            // intentionally outside retrieval exception handling and aborts work.
            $heartbeat?->__invoke();
            $summary['policies']++;

            $checkpoints->interruptOpenAttempts((int) $policy->id);
            $policyRunId = $checkpoints->startPolicyRun((int) $policy->id, $executionRunId);

            try {
                app(MonitoringPolicyValidationService::class)->validate($policy);
            } catch (Throwable $exception) {
                // Validation is a policy failure, not a source retrieval failure.
                // Persist only a fixed code; never persist exception messages.
                $checkpoints->failPolicyRun($policyRunId, 'policy_validation_failed');
                throw $exception;
            }

            $heartbeat?->__invoke();
            $sources = $policy->source_id
                ? Source::whereKey($policy->source_id)->get()
                : Source::where('status', 'active')->whereNotNull('url')->get();

            $failed = false;

            foreach ($sources as $source) {
                $heartbeat?->__invoke();
                $summary['sources']++;
                $checkpoints->startSource($policyRunId, (int) $source->id);

                try {
                    $result = app(SourceMonitoringPipeline::class)->run($source);
                } catch (Throwable $exception) {
                    // Confirm ownership before classifying a thrown pipeline error
                    // as a source failure. A heartbeat failure must escape unchanged.
                    $heartbeat?->__invoke();
                    $summary['failed']++;
                    $failed = true;
                    $exceptionClass = mb_substr(get_class($exception), 0, 160);

                    $checkpoints->failSource($policyRunId, (int) $source->id, $exceptionClass);
                    app(MonitoringAlertService::class)->raise(
                        'retrieval_failure',
                        app(MonitoringAlertPolicyService::class)->severityForFailures($policy->consecutive_failures + 1),
                        'Source monitoring pipeline failed.',
                        $source,
                        $policy,
                        ['exception_class' => $exceptionClass]
                    );
                    app(MonitoringAuditService::class)->record(
                        'retrieval_failure',
                        'Source monitoring pipeline failed.',
                        $source->id,
                        'warning',
                        ['exception_class' => $exceptionClass, 'policy_id' => $policy->id]
                    );

                    continue;
                }

                // The pipeline may have taken long enough for ownership to expire.
                // Do not project outcomes or checkpoint success until renewed.
                $heartbeat?->__invoke();

                if (($result['run']->status ?? '') !== 'completed') {
                    $summary['failed']++;
                    $failed = true;
                    $runId = $result['run']->id ?? null;
                    $checkpoints->failSource(
                        $policyRunId,
                        (int) $source->id,
                        'retrieval_not_completed',
                        $runId !== null ? (int) $runId : null
                    );
                    app(MonitoringAlertService::class)->raise(
                        'retrieval_failure',
                        app(MonitoringAlertPolicyService::class)->severityForFailures($policy->consecutive_failures + 1),
                        'Source retrieval failed.',
                        $source,
                        $policy
                    );
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

                // Check ownership again immediately before recording completion.
                $heartbeat?->__invoke();
                $checkpoints->completeSource(
                    $policyRunId,
                    (int) $source->id,
                    isset($result['run']->id) ? (int) $result['run']->id : null,
                    $observation?->id !== null ? (int) $observation->id : null
                );
            }

            $heartbeat?->__invoke();
            // State changes, last_run_at and final policy checkpoint are one DB
            // transaction. Any persistence exception rolls all three back.
            $checkpoints->finalizePolicy($policy, $policyRunId, $failed, $summary);
        }

        return $summary;
    }
}

<?php

namespace App\Services;

use App\Models\Source;

class SourceMonitoringBatchService
{
    public function run(?int $policyId = null): array
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
            'discovery_candidates' => 0,
            'discovery_duplicates' => 0,
        ];

        foreach ($policies as $policy) {
            $summary['policies']++;
            app(MonitoringPolicyValidationService::class)->validate($policy);

            $sources = $policy->source_id
                ? Source::whereKey($policy->source_id)->get()
                : Source::where('status', 'active')->whereNotNull('url')->get();

            $failed = false;

            foreach ($sources as $source) {
                $summary['sources']++;
                $result = app(SourceMonitoringPipeline::class)->run($source);

                if (($result['run']->status ?? '') !== 'completed') {
                    $summary['failed']++;
                    $failed = true;
                    app(MonitoringAlertService::class)->raise(
                        'retrieval_failure',
                        app(MonitoringAlertPolicyService::class)->severityForFailures($policy->consecutive_failures + 1),
                        'Source retrieval failed: '.($result['run']->error_message ?? 'unknown error'),
                        $source,
                        $policy
                    );
                    app(MonitoringAuditService::class)->record(
                        'retrieval_failure',
                        'Source retrieval failed',
                        $source->id,
                        'warning'
                    );
                    continue;
                }

                $summary['completed']++;

                if (($result['observation']?->change_state) === 'changed') {
                    $summary['changed']++;
                    $summary['queued'] += ($result['intelligence']['queued'] ?? 0);

                    $impact = $result['observation']->materiality ?? 'unknown';
                    app(MonitoringAlertService::class)->raise(
                        'source_change',
                        app(MonitoringAlertPolicyService::class)->severityForChange($impact),
                        'Monitored source content changed.',
                        $source,
                        $policy,
                        ['observation_id' => $result['observation']->id]
                    );
                    app(MonitoringAuditService::class)->record(
                        'source_change',
                        'Monitored source content changed',
                        $source->id,
                        'info',
                        ['observation_id' => $result['observation']->id]
                    );

                    $discovery = app(SourceChangeDiscoveryService::class)->process($result['observation']);
                    if (is_array($discovery)) {
                        $summary['discovery_batches']++;
                        $summary['discovery_candidates'] += (int) ($discovery['candidate_rows'] ?? 0);
                        $summary['discovery_duplicates'] += (int) ($discovery['duplicate_rows'] ?? 0);
                    }
                }
            }

            $failed
                ? app(MonitoringPolicyStateService::class)->failure($policy)
                : app(MonitoringPolicyStateService::class)->success($policy);

            $policy->update(['last_run_at' => now()]);
        }

        return $summary;
    }
}

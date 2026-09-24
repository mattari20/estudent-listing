<?php

namespace App\Services;

use App\Models\ImportBatch;

class InstituteReadinessContractService
{
    public function summarize(ImportBatch $batch): array
    {
        $deployment = app(InstituteDeploymentReadinessService::class)->summarize($batch);

        $checks = [
            'deployment_readiness' => $deployment['deployment_ready'],
            'mutation_free' => $deployment['mutation_performed'] === false,
            'has_batch_context' => isset($deployment['batch']['id'], $deployment['batch']['source_id']),
            'has_blocking_findings' => isset($deployment['blocking_findings'], $deployment['blocking_total']),
        ];

        $contractReady = !in_array(false, $checks, true);

        return [
            'scope' => 'read_only_readiness_contract',
            'batch' => $deployment['batch'],
            'deployment_readiness' => $deployment,
            'contract_checks' => $checks,
            'blocking_total' => $contractReady ? 0 : 1,
            'contract_ready' => $contractReady,
            'mutation_performed' => false,
        ];
    }
}

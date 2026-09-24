<?php

namespace App\Services;

use App\Models\ImportBatch;

class InstituteDeploymentReadinessService
{
    public function summarize(ImportBatch $batch): array
    {
        $final = app(InstituteFinalReadinessService::class)->summarize($batch);
        $data = app(InstituteDataReadinessService::class)->summarize();

        $blocking = [
            'final_readiness_not_clear' => $final['production_ready'] ? 0 : 1,
            'structural_readiness_not_clear' => $data['structurally_ready'] ? 0 : 1,
        ];

        return [
            'scope' => 'read_only_deployment_readiness',
            'batch' => $final['batch'],
            'final_readiness' => $final,
            'structural_readiness' => $data,
            'blocking_findings' => $blocking,
            'blocking_total' => array_sum($blocking),
            'deployment_ready' => array_sum($blocking) === 0,
            'mutation_performed' => false,
        ];
    }
}

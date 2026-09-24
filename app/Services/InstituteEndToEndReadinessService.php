<?php

namespace App\Services;

use App\Models\ImportBatch;

class InstituteEndToEndReadinessService
{
    public function summarize(ImportBatch $batch): array
    {
        $contract = app(InstituteReadinessContractService::class)->summarize($batch);
        $deployment = $contract['deployment_readiness'];

        $checks = [
            'readiness_contract' => $contract['contract_ready'],
            'deployment_readiness' => $deployment['deployment_ready'],
            'final_production_readiness' => $deployment['final_readiness']['production_ready'],
            'structural_readiness' => $deployment['structural_readiness']['structurally_ready'],
            'mutation_free' => $contract['mutation_performed'] === false,
        ];

        $blocking = collect($checks)->filter(fn ($passed) => !$passed)->count();

        return [
            'scope' => 'read_only_end_to_end_readiness_validation',
            'batch' => $contract['batch'],
            'checks' => $checks,
            'blocking_total' => $blocking,
            'end_to_end_ready' => $blocking === 0,
            'mutation_performed' => false,
        ];
    }
}

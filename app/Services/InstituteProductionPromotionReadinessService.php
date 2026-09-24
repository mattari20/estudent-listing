<?php

namespace App\Services;

use App\Models\ImportBatch;

class InstituteProductionPromotionReadinessService
{
    public function summarize(ImportBatch $batch): array
    {
        $import = app(InstituteImportVerificationService::class)->summarize($batch);
        $source = app(InstituteSourceVerificationReadinessService::class)->summarize($batch);
        $provenance = app(InstituteProvenanceReadinessService::class)->summarize($batch);

        $blocking = [
            'import_verification_not_ready' => $import['verification_ready'] ? 0 : 1,
            'source_verification_not_ready' => $source['source_verification_ready'] ? 0 : 1,
            'provenance_verification_not_ready' => $provenance['provenance_ready'] ? 0 : 1,
        ];

        return [
            'scope' => 'read_only_production_promotion_readiness',
            'batch' => $import['batch'],
            'import_verification' => $import,
            'source_verification' => $source,
            'provenance_verification' => $provenance,
            'blocking_findings' => $blocking,
            'blocking_total' => array_sum($blocking),
            'promotion_ready' => array_sum($blocking) === 0,
            'mutation_performed' => false,
        ];
    }
}

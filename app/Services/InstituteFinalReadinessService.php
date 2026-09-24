<?php

namespace App\Services;

use App\Models\ImportBatch;
use App\Models\SourceRetrievalRun;

class InstituteFinalReadinessService
{
    public function summarize(ImportBatch $batch): array
    {
        $promotion = app(InstituteProductionPromotionReadinessService::class)->summarize($batch);
        $latestRun = SourceRetrievalRun::query()
            ->where('source_id', $batch->source_id)
            ->latest('created_at')
            ->first();

        $lineageConsistent = (bool) ($latestRun
            && $latestRun->status === 'completed'
            && $latestRun->snapshot_id
            && $latestRun->content_hash);

        $blocking = [
            'promotion_readiness_not_clear' => $promotion['promotion_ready'] ? 0 : 1,
            'latest_retrieval_lineage_incomplete' => $lineageConsistent ? 0 : 1,
        ];

        return [
            'scope' => 'read_only_final_production_readiness',
            'batch' => $promotion['batch'],
            'promotion_readiness' => $promotion,
            'latest_retrieval' => [
                'id' => $latestRun?->id,
                'status' => $latestRun?->status,
                'snapshot_id' => $latestRun?->snapshot_id,
                'content_hash_present' => (bool) $latestRun?->content_hash,
            ],
            'blocking_findings' => $blocking,
            'blocking_total' => array_sum($blocking),
            'production_ready' => array_sum($blocking) === 0,
            'mutation_performed' => false,
        ];
    }
}

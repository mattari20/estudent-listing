<?php

namespace App\Services;

use App\Models\ImportBatch;

class InstituteOperatorReadinessDashboardService
{
    public function summarize(): array
    {
        $batches = ImportBatch::query()->latest()->limit(20)->get();

        $items = [];
        foreach ($batches as $batch) {
            $deployment = app(InstituteDeploymentReadinessService::class)->summarize($batch);
            $items[] = [
                'batch_id' => $batch->id,
                'batch_key' => $batch->batch_key,
                'status' => $batch->status,
                'source_id' => $batch->source_id,
                'deployment_ready' => $deployment['deployment_ready'],
                'blocking_total' => $deployment['blocking_total'],
            ];
        }

        return [
            'scope' => 'read_only_operator_readiness_dashboard',
            'batch_count' => count($items),
            'ready_batches' => count(array_filter($items, fn ($item) => $item['deployment_ready'])),
            'blocked_batches' => count(array_filter($items, fn ($item) => !$item['deployment_ready'])),
            'batches' => $items,
            'mutation_performed' => false,
        ];
    }
}

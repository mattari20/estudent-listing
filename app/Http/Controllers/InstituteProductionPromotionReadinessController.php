<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\InstituteProductionPromotionReadinessService;
use Illuminate\Http\JsonResponse;

class InstituteProductionPromotionReadinessController
{
    public function __invoke(ImportBatch $importBatch, InstituteProductionPromotionReadinessService $service): JsonResponse
    {
        return response()->json($service->summarize($importBatch));
    }
}

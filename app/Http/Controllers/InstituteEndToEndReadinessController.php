<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\InstituteEndToEndReadinessService;
use Illuminate\Http\JsonResponse;

class InstituteEndToEndReadinessController
{
    public function __invoke(ImportBatch $importBatch, InstituteEndToEndReadinessService $service): JsonResponse
    {
        return response()->json($service->summarize($importBatch));
    }
}

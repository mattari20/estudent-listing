<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\InstituteFinalReadinessService;
use Illuminate\Http\JsonResponse;

class InstituteFinalReadinessController
{
    public function __invoke(ImportBatch $importBatch, InstituteFinalReadinessService $service): JsonResponse
    {
        return response()->json($service->summarize($importBatch));
    }
}

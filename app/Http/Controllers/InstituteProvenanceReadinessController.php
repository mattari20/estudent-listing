<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\InstituteProvenanceReadinessService;
use Illuminate\Http\JsonResponse;

class InstituteProvenanceReadinessController
{
    public function __invoke(ImportBatch $importBatch, InstituteProvenanceReadinessService $service): JsonResponse
    {
        return response()->json($service->summarize($importBatch));
    }
}

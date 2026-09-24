<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\InstituteDeploymentReadinessService;
use Illuminate\Http\JsonResponse;

class InstituteDeploymentReadinessController
{
    public function __invoke(ImportBatch $importBatch, InstituteDeploymentReadinessService $service): JsonResponse
    {
        return response()->json($service->summarize($importBatch));
    }
}

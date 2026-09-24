<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\InstituteReadinessContractService;
use Illuminate\Http\JsonResponse;

class InstituteReadinessContractController
{
    public function __invoke(ImportBatch $importBatch, InstituteReadinessContractService $service): JsonResponse
    {
        return response()->json($service->summarize($importBatch));
    }
}

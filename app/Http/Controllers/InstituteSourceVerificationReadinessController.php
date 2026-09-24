<?php
namespace App\Http\Controllers;
use App\Models\ImportBatch;
use App\Services\InstituteSourceVerificationReadinessService;
use Illuminate\Http\JsonResponse;
class InstituteSourceVerificationReadinessController
{
    public function __invoke(ImportBatch $importBatch, InstituteSourceVerificationReadinessService $service): JsonResponse
    {
        return response()->json($service->summarize($importBatch));
    }
}

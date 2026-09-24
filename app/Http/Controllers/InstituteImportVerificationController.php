<?php
namespace App\Http\Controllers;
use App\Models\ImportBatch;
use App\Services\InstituteImportVerificationService;
use Illuminate\Http\JsonResponse;
class InstituteImportVerificationController
{
    public function __invoke(ImportBatch $importBatch, InstituteImportVerificationService $verification): JsonResponse
    {
        return response()->json($verification->summarize($importBatch));
    }
}

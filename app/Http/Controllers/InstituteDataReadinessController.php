<?php

namespace App\Http\Controllers;

use App\Services\InstituteDataReadinessService;
use Illuminate\Http\JsonResponse;

class InstituteDataReadinessController
{
    public function __invoke(InstituteDataReadinessService $readiness): JsonResponse
    {
        return response()->json($readiness->summarize());
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\InstituteOperatorReadinessDashboardService;
use Illuminate\Http\JsonResponse;

class InstituteOperatorReadinessDashboardController
{
    public function __invoke(InstituteOperatorReadinessDashboardService $service): JsonResponse
    {
        return response()->json($service->summarize());
    }
}

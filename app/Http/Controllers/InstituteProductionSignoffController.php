<?php

namespace App\Http\Controllers;

use App\Services\InstituteProductionSignoffService;
use Illuminate\Http\JsonResponse;

class InstituteProductionSignoffController
{
    public function __invoke(InstituteProductionSignoffService $service): JsonResponse
    {
        return response()->json($service->summarize());
    }
}

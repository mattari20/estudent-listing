<?php

namespace App\Http\Controllers;

use App\Services\InstituteImportDuplicateAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class InstituteImportDuplicateAuditController
{
    public function view(InstituteImportDuplicateAuditService $audit): View
    {
        return view('institutes.operations.duplicate-audit', ['report' => $audit->build()]);
    }

    public function __invoke(InstituteImportDuplicateAuditService $audit): JsonResponse
    {
        return response()->json($audit->build(), 200, ['Cache-Control' => 'no-store, private']);
    }
}

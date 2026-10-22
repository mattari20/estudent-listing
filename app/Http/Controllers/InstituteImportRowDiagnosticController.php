<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\InstituteImportRowDiagnosticService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class InstituteImportRowDiagnosticController
{
    public function view(ImportBatch $importBatch, InstituteImportRowDiagnosticService $diagnostics): View
    {
        abort_unless($importBatch->entity_type === 'institution', 404);

        $report = $diagnostics->build($importBatch);

        return view('institutes.operations.row-diagnostics', compact('report'));
    }

    public function __invoke(
        ImportBatch $importBatch,
        InstituteImportRowDiagnosticService $diagnostics
    ): JsonResponse {
        abort_unless($importBatch->entity_type === 'institution', 404);

        return response()->json(
            $diagnostics->build($importBatch),
            200,
            ['Cache-Control' => 'no-store, private']
        );
    }
}

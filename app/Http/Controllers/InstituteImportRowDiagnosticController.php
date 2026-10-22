<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\InstituteImportRowDiagnosticService;
use Illuminate\Http\JsonResponse;

class InstituteImportRowDiagnosticController
{
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

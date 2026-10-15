<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class AutomatedDiagnosticsController
{
    public function __invoke(Request $request): JsonResponse
    {
        $configuredToken = (string) config('services.institutes_diagnostics.token', '');

        // Keep the endpoint undiscoverable until an owner-managed secret is configured.
        if ($configuredToken === '' || strlen($configuredToken) < 32) {
            abort(404);
        }

        $providedToken = (string) $request->bearerToken();
        if ($providedToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            abort(403);
        }

        $exitCode = Artisan::call('institutes:data-gap-report', ['--json' => true]);
        $output = trim(Artisan::output());
        $report = json_decode($output, true);

        if (! is_array($report) || ($report['check'] ?? null) !== 'institutes_data_gap_report') {
            return response()->json([
                'status' => 'critical',
                'error' => 'diagnostic_report_unavailable',
                'mutation_performed' => false,
            ], 500, ['Cache-Control' => 'no-store']);
        }

        return response()->json([
            'status' => $report['status'] ?? 'unknown',
            'diagnostic_exit_code' => $exitCode,
            'report' => $report,
        ], 200, ['Cache-Control' => 'no-store']);
    }
}

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

        $reportCommands = [
            'report' => 'institutes:data-gap-report',
            'record_audit' => 'institutes:data-gap-record-audit',
            'geography_preflight' => 'institutes:geography-seed-preflight',
        ];

        $reports = [];
        foreach ($reportCommands as $key => $command) {
            $exitCode = Artisan::call($command, ['--json' => true]);
            $output = trim(Artisan::output());
            $decoded = json_decode($output, true);

            $reports[$key] = [
                'exit_code' => $exitCode,
                'data' => is_array($decoded) ? $decoded : null,
            ];
        }

        $allReadOnly = true;
        foreach ($reports as $entry) {
            $data = $entry['data'] ?? null;
            if (! is_array($data) || ($data['mutation_performed'] ?? null) !== false) {
                $allReadOnly = false;
            }
        }

        $geographyData = $reports['geography_preflight']['data'] ?? null;
        if (! is_array($geographyData) || ($geographyData['seed_command_executed'] ?? null) !== false) {
            $allReadOnly = false;
        }

        $hasFailures = false;
        foreach ($reports as $entry) {
            if (($entry['exit_code'] ?? 1) !== 0) {
                $hasFailures = true;
            }
        }

        $status = ! $allReadOnly ? 'critical' : ($hasFailures ? 'warning' : 'complete');

        return response()->json([
            'status' => $status,
            'mutation_performed' => ! $allReadOnly,
            'reports' => $reports,
        ])->header('Cache-Control', 'no-store, private');
    }
}

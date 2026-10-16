<?php

namespace App\\Http\\Controllers;

use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Artisan;

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

        $reports = [
            'report' => [
                'command' => 'institutes:data-gap-report',
                'check' => 'institutes_data_gap_report',
            ],
            'record_audit' => [
                'command' => 'institutes:data-gap-record-audit',
                'check' => 'institutes_data_gap_record_audit',
            ],
            'geography_preflight' => [
                'command' => 'institutes:geography-seed-preflight',
                'check' => 'institutes_geography_seed_preflight',
            ],
        ];

        $payload = [
            'status' => 'complete',
            'mutation_performed' => false,
            'reports' => [],
        ];

        foreach ($reports as $key => $definition) {
            $exitCode = Artisan::call($definition['command'], ['--json' => true]);
            $output = trim(Artisan::output());
            $decoded = json_decode($output, true);

            if (
                ! is_array($decoded)
                || ($decoded['check'] ?? null) !== $definition['check']
                || ($decoded['mutation_performed'] ?? null) !== false
            ) {
                return $this->unavailable();
            }

            $payload['reports'][$key] = [
                'exit_code' => $exitCode,
                'data' => $decoded,
            ];

            if ($exitCode !== 0 || ($decoded['status'] ?? null) === 'critical') {
                $payload['status'] = 'critical';
            } elseif (($decoded['status'] ?? null) === 'warning' && $payload['status'] === 'complete') {
                $payload['status'] = 'warning';
            }
        }

        return response()->json($payload, 200, ['Cache-Control' => 'no-store']);
    }

    private function unavailable(): JsonResponse
    {
        return response()->json([
            'status' => 'critical',
            'error' => 'diagnostic_report_unavailable',
            'mutation_performed' => false,
        ], 500, ['Cache-Control' => 'no-store']);
    }
}

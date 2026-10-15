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

        $aggregateExitCode = Artisan::call('institutes:data-gap-report', ['--json' => true]);
        $aggregateOutput = trim(Artisan::output());
        $aggregateReport = json_decode($aggregateOutput, true);

        if (! is_array($aggregateReport) || ($aggregateReport['check'] ?? null) !== 'institutes_data_gap_report') {
            return $this->unavailable();
        }

        $recordAuditExitCode = Artisan::call('institutes:data-gap-record-audit', ['--json' => true]);
        $recordAuditOutput = trim(Artisan::output());
        $recordAudit = json_decode($recordAuditOutput, true);

        if (
            ! is_array($recordAudit)
            || ($recordAudit['check'] ?? null) !== 'institutes_data_gap_record_audit'
            || ($recordAudit['mutation_performed'] ?? null) !== false
        ) {
            return $this->unavailable();
        }

        return response()->json([
            'status' => $aggregateReport['status'] ?? 'unknown',
            'diagnostic_exit_code' => $aggregateExitCode,
            'report' => $aggregateReport,
            'record_audit_exit_code' => $recordAuditExitCode,
            'record_audit' => $recordAudit,
        ], 200, ['Cache-Control' => 'no-store']);
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

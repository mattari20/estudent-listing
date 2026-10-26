<?php

namespace App\\Console\\Commands;

use Illuminate\\Console\\Command;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

class ImportIntegrityAudit extends Command
{
    protected $signature = 'institutes:import-integrity-audit {--json : Print JSON output}';
    protected $description = 'Read-only summary of import row states and sanitized failed-batch diagnostics.';

    public function handle(): int
    {
        $report = [
            'check' => 'institutes_import_integrity_audit',
            'checked_at' => now()->toIso8601String(),
            'mutation_performed' => false,
            'database_mutations' => 0,
            'groups' => [],
            'failed_batches' => [],
        ];

        if (! Schema::hasTable('import_rows') || ! Schema::hasTable('import_batches')) {
            $report['status'] = 'critical';
            $report['reason'] = 'required_import_tables_missing';
            $this->line(json_encode($report, JSON_PRETTY_PRINT));
            return self::FAILURE;
        }

        $report['groups'] = DB::table('import_rows')
            ->join('import_batches', 'import_batches.id', '=', 'import_rows.import_batch_id')
            ->select('import_batches.entity_type', 'import_rows.status', DB::raw('COUNT(*) as total'))
            ->groupBy('import_batches.entity_type', 'import_rows.status')
            ->orderBy('import_batches.entity_type')
            ->orderBy('import_rows.status')
            ->get()
            ->map(fn ($row) => [
                'entity_type' => $row->entity_type,
                'status' => $row->status,
                'count' => (int) $row->total,
            ])->all();

        // Expose stable identifiers and safe categories only. Never return
        // raw notes, payloads, exception messages, credentials, or source data.
        $failedBatches = DB::table('import_batches')
            ->where('status', 'failed')
            ->orderBy('id')
            ->get(['id', 'entity_type', 'source_type', 'status', 'received_at', 'completed_at', 'notes']);

        $report['failed_batches'] = $failedBatches->map(fn ($batch) => [
            'batch_id' => (int) $batch->id,
            'entity_type' => $batch->entity_type,
            'source_type' => $batch->source_type,
            'status' => $batch->status,
            'received_at' => $batch->received_at,
            'completed_at' => $batch->completed_at,
            'failure_category' => $this->safeFailureCategory((string) ($batch->notes ?? '')),
            'category_is_inferred_not_root_cause' => true,
        ])->all();

        // "completed" describes successful execution, not data health.
        $report['status'] = 'completed';
        $report['interpretation'] = 'Audit execution completed; grouped states and failed-batch categories require review and do not certify data health.';

        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return self::SUCCESS;
    }

    private function safeFailureCategory(string $notes): string
    {
        $text = mb_strtolower($notes);

        if ($text === '') {
            return 'reason_not_recorded';
        }

        if (preg_match('/duplicate|header|schema|column|csv format/', $text)) {
            return 'schema_or_csv_validation';
        }

        if (preg_match('/validation|required|invalid field|missing field/', $text)) {
            return 'row_validation';
        }

        if (preg_match('/source|url|evidence|fetch|http/', $text)) {
            return 'source_or_evidence';
        }

        if (preg_match('/timeout|network|connection/', $text)) {
            return 'transient_infrastructure';
        }

        return 'other_or_unclassified';
    }
}

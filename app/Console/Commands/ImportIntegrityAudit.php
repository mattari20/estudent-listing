<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportIntegrityAudit extends Command
{
    protected $signature = 'institutes:import-integrity-audit {--json : Print JSON output}';
    protected $description = 'Read-only summary of import row states by entity type.';

    public function handle(): int
    {
        $report = [
            'check' => 'institutes_import_integrity_audit',
            'checked_at' => now()->toIso8601String(),
            'mutation_performed' => false,
            'groups' => [],
        ];

        if (! Schema::hasTable('import_rows') || ! Schema::hasTable('import_batches')) {
            $report['status'] = 'critical';
            $report['reason'] = 'required_import_tables_missing';
            $this->line(json_encode($report, JSON_PRETTY_PRINT));
            return self::FAILURE;
        }

        $report['status'] = 'healthy';
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

        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return self::SUCCESS;
    }
}

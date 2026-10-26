<?php

namespace Tests\\Feature;

use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Illuminate\\Support\\Facades\\Artisan;
use Illuminate\\Support\\Facades\\DB;
use Tests\\TestCase;

class ImportIntegrityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_integrity_audit_groups_rows_without_mutating_them(): void
    {
        $batchId = DB::table('import_batches')->insertGetId([
            'batch_key' => 'integrity-audit-test',
            'entity_type' => 'campus',
            'source_type' => 'manual_import',
            'status' => 'validated',
            'total_rows' => 1,
            'valid_rows' => 1,
            'invalid_rows' => 0,
            'candidate_rows' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rowId = DB::table('import_rows')->insertGetId([
            'import_batch_id' => $batchId,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'unresolved',
            'payload' => json_encode(['name' => 'Test Campus']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $exitCode = Artisan::call('institutes:import-integrity-audit', ['--json' => true]);
        $output = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame('completed', $output['status']);
        $this->assertStringContainsString('do not certify', $output['interpretation']);
        $this->assertFalse($output['mutation_performed']);
        $this->assertSame(0, $output['database_mutations']);
        $this->assertSame([
            ['entity_type' => 'campus', 'status' => 'candidate', 'count' => 1],
        ], $output['groups']);
        $this->assertSame([], $output['failed_batches']);
        $this->assertDatabaseHas('import_rows', [
            'id' => $rowId,
            'status' => 'candidate',
            'match_state' => 'unresolved',
        ]);
        $this->assertDatabaseHas('import_batches', [
            'id' => $batchId,
            'status' => 'validated',
        ]);
    }

    public function test_failed_batch_diagnostics_expose_safe_category_without_raw_notes(): void
    {
        $batchId = DB::table('import_batches')->insertGetId([
            'batch_key' => 'integrity-audit-failed-test',
            'entity_type' => 'institution',
            'source_type' => 'manual_import',
            'status' => 'failed',
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'candidate_rows' => 0,
            'notes' => 'Duplicate column names are not allowed. Sensitive raw detail must not leak.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $exitCode = Artisan::call('institutes:import-integrity-audit', ['--json' => true]);
        $output = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame(1, count($output['failed_batches']));
        $this->assertSame($batchId, $output['failed_batches'][0]['batch_id']);
        $this->assertSame('schema_or_csv_validation', $output['failed_batches'][0]['failure_category']);
        $this->assertTrue($output['failed_batches'][0]['category_is_inferred_not_root_cause']);
        $this->assertStringNotContainsString('Sensitive raw detail', json_encode($output));
        $this->assertFalse($output['mutation_performed']);
        $this->assertSame(0, $output['database_mutations']);
        $this->assertDatabaseHas('import_batches', ['id' => $batchId, 'status' => 'failed']);
    }
}

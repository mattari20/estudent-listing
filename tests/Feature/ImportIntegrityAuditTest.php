<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

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
        $this->assertStringContainsString('does not certify', $output['interpretation']);
        $this->assertFalse($output['mutation_performed']);
        $this->assertSame([
            ['entity_type' => 'campus', 'status' => 'candidate', 'count' => 1],
        ], $output['groups']);
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
}

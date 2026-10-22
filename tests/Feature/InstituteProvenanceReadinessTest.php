<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Source;
use App\Services\InstituteProvenanceReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteProvenanceReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_provenance_gate_blocks_candidate_rows_without_lineage_or_complete_assertion(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Official Source',
            'url' => 'https://example.com',
            'status' => 'active',
        ]);

        $batch = ImportBatch::create([
            'batch_key' => 'DEV-310-TEST',
            'entity_type' => 'institution',
            'source_type' => 'official',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'candidate_rows' => 0,
            'received_at' => now(),
        ]);

        ImportRow::create(['import_batch_id' => $batch->id, 'row_number' => 1, 'status' => 'candidate', 'payload' => ['name' => 'Example Institute']]);

        $summary = (new InstituteProvenanceReadinessService)->summarize($batch);

        $this->assertFalse($summary['provenance_ready']);
        $this->assertSame(2, $summary['blocking_total']);
        $this->assertFalse($summary['mutation_performed']);
    }

    public function test_candidate_without_evidence_is_blocked_by_provenance_gate(): void
 {
  $source = Source::create(['source_type' => 'official', 'name' => 'Evidence Gate Source', 'url' => 'https://example.test/evidence-gate', 'status' => 'active']);
  $batch = ImportBatch::create(['batch_key' => 'provenance-no-evidence-'.uniqid(), 'entity_type' => 'institution', 'source_type' => 'official', 'source_id' => $source->id, 'status' => 'validated', 'total_rows' => 1, 'valid_rows' => 1, 'invalid_rows' => 0, 'candidate_rows' => 1, 'received_at' => now()]);
  ImportRow::create(['import_batch_id' => $batch->id, 'row_number' => 1, 'status' => 'candidate', 'payload' => ['name' => 'Evidence Missing Institute']]);
  $summary = (new InstituteProvenanceReadinessService)->summarize($batch);
  $this->assertFalse($summary['provenance_ready']);
  $this->assertFalse($summary['mutation_performed']);
 }
}

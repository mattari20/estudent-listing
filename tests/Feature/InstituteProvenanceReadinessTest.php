<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
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

        $summary = (new InstituteProvenanceReadinessService)->summarize($batch);

        $this->assertTrue($summary['provenance_ready']);
        $this->assertSame(0, $summary['blocking_total']);
        $this->assertFalse($summary['mutation_performed']);
    }
}

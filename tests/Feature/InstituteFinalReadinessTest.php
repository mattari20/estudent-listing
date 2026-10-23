<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\Source;
use App\Models\SourceRetrievalRun;
use App\Services\InstituteFinalReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteFinalReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_readiness_blocks_without_latest_completed_retrieval_lineage(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Official Source',
            'url' => 'https://example.com',
            'status' => 'active',
        ]);

        $batch = ImportBatch::create([
            'batch_key' => 'DEV-311-TEST',
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

        SourceRetrievalRun::create([
            'source_id' => $source->id,
            'status' => 'failed',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $summary = (new InstituteFinalReadinessService)->summarize($batch);

        $this->assertFalse($summary['production_ready']);
        $this->assertSame(2, $summary['blocking_total']);
        $this->assertFalse($summary['mutation_performed']);
    }


    public function test_blocked_final_readiness_summary_does_not_change_batch(): void
    {
        $source = Source::create(['source_type' => 'official', 'name' => 'Read Only Final Source', 'url' => 'https://example.test/final-readiness', 'status' => 'active']);
        $batch = ImportBatch::create(['batch_key' => 'readonly-final-' . uniqid(), 'entity_type' => 'institution', 'source_type' => 'official', 'source_id' => $source->id, 'status' => 'validated', 'total_rows' => 0, 'valid_rows' => 0, 'invalid_rows' => 0, 'candidate_rows' => 0, 'received_at' => now()]);
        $beforeStatus = $batch->status;

        $summary = (new InstituteFinalReadinessService)->summarize($batch);

        $this->assertFalse($summary['final_ready'] ?? $summary['readiness_ready'] ?? true);
        $this->assertSame($beforeStatus, $batch->fresh()->status);
        $this->assertFalse($summary['mutation_performed'] ?? true);
    }
}

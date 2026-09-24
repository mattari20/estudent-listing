<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\Source;
use App\Services\InstituteProductionPromotionReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteProductionPromotionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_readiness_is_read_only_and_blocks_when_import_or_source_gate_is_not_ready(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Official Source',
            'url' => 'https://example.com',
            'status' => 'active',
        ]);

        $batch = ImportBatch::create([
            'batch_key' => 'DEV-309-TEST',
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

        $summary = (new InstituteProductionPromotionReadinessService)->summarize($batch);

        $this->assertFalse($summary['promotion_ready']);
        $this->assertSame(2, $summary['blocking_total']);
        $this->assertFalse($summary['mutation_performed']);
        $this->assertFalse($summary['import_verification']['mutation_performed']);
        $this->assertFalse($summary['source_verification']['mutation_performed']);
    }
}

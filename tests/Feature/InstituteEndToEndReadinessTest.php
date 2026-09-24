<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\Source;
use App\Services\InstituteEndToEndReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteEndToEndReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_end_to_end_validation_reports_all_readiness_layers_and_is_read_only(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Official Source',
            'url' => 'https://example.com',
            'status' => 'active',
        ]);

        $batch = ImportBatch::create([
            'batch_key' => 'DEV-315-TEST',
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

        $summary = (new InstituteEndToEndReadinessService)->summarize($batch);

        $this->assertSame([
            'readiness_contract' => false,
            'deployment_readiness' => false,
            'final_production_readiness' => false,
            'structural_readiness' => true,
            'mutation_free' => true,
        ], $summary['checks']);

        $this->assertFalse($summary['end_to_end_ready']);
        $this->assertGreaterThan(0, $summary['blocking_total']);
        $this->assertFalse($summary['mutation_performed']);
    }
}

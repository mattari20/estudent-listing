<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\Source;
use App\Services\InstituteDeploymentReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteDeploymentReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_deployment_gate_is_read_only_and_requires_all_readiness_layers(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Official Source',
            'url' => 'https://example.com',
            'status' => 'active',
        ]);

        $batch = ImportBatch::create([
            'batch_key' => 'DEV-312-TEST',
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

        $summary = (new InstituteDeploymentReadinessService)->summarize($batch);

        $this->assertFalse($summary['deployment_ready']);
        $this->assertGreaterThan(0, $summary['blocking_total']);
        $this->assertFalse($summary['mutation_performed']);
    }
}

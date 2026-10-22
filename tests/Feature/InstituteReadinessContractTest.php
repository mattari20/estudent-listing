<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\Source;
use App\Services\InstituteReadinessContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteReadinessContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_contract_is_structurally_stable_and_read_only(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Official Source',
            'url' => 'https://example.com',
            'status' => 'active',
        ]);

        $batch = ImportBatch::create([
            'batch_key' => 'DEV-313-TEST',
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

        $summary = (new InstituteReadinessContractService)->summarize($batch);

        $this->assertFalse($summary['contract_ready']);
        $this->assertTrue($summary['contract_checks']['mutation_free']);
        $this->assertTrue($summary['contract_checks']['has_batch_context']);
        $this->assertTrue($summary['contract_checks']['has_blocking_findings']);
        $this->assertFalse($summary['mutation_performed']);
    }

    public function test_readiness_contract_always_reports_mutation_free_check(): void
    {
        $source = Source::query()->create(['source_type'=>'test','name'=>'Contract source','url'=>'https://example.test/contract','status'=>'active']);
        $batch = ImportBatch::query()->create(['batch_key'=>'contract-read-only','entity_type'=>'institution','source_type'=>'test','source_id'=>$source->id,'status'=>'validated','total_rows'=>0,'valid_rows'=>0,'invalid_rows'=>0,'candidate_rows'=>0,'received_at'=>now()]);
        $summary = (new InstituteReadinessContractService)->summarize($batch);
        $this->assertTrue($summary['contract_checks']['mutation_free']);
        $this->assertFalse($summary['mutation_performed']);
    }
}

<?php
namespace Tests\Feature;
use App\Models\Evidence;
use App\Models\ImportBatch;
use App\Models\Source;
use App\Models\SourceMonitoringPolicy;
use App\Models\SourceRetrievalRun;
use App\Models\SourceSnapshot;
use App\Services\InstituteSourceVerificationReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SourceVerificationReadinessTest extends TestCase
{
    use RefreshDatabase;
    public function test_source_verification_requires_evidence_policy_and_successful_retrieval(): void
    {
        $source=Source::create(['source_type'=>'official','name'=>'Official Source','url'=>'https://example.com','status'=>'active']);
        $batch=ImportBatch::create(['batch_key'=>'src-test-1','entity_type'=>'institution','source_type'=>'official','source_id'=>$source->id,'status'=>'validated','total_rows'=>1,'valid_rows'=>1,'invalid_rows'=>0,'candidate_rows'=>1]);
        $summary=(new InstituteSourceVerificationReadinessService)->summarize($batch);
        $this->assertFalse($summary['source_verification_ready']);
        $this->assertSame(5,$summary['blocking_total']);
        $this->assertFalse($summary['mutation_performed']);
    }
    public function test_source_verification_passes_with_active_fresh_evidence_policy_and_completed_retrieval(): void
    {
        $source=Source::create(['source_type'=>'official','name'=>'Official Source','url'=>'https://example.com','status'=>'active']);
        $batch=ImportBatch::create(['batch_key'=>'src-test-2','entity_type'=>'institution','source_type'=>'official','source_id'=>$source->id,'status'=>'validated','total_rows'=>1,'valid_rows'=>1,'invalid_rows'=>0,'candidate_rows'=>1]);
        Evidence::create(['source_id'=>$source->id,'reference'=>'official-page','excerpt'=>'Official institute information','content_hash'=>'hash-1','observed_at'=>now(),'status'=>'active']);
        SourceMonitoringPolicy::create(['name'=>'Official source policy','subject_type'=>null,'attribute'=>'institution','fresh_after_days'=>30,'stale_after_days'=>90,'priority'=>'normal','status'=>'active','source_id'=>$source->id,'cadence'=>'monthly']);
        $snapshot=SourceSnapshot::create(['source_id'=>$source->id,'reference'=>'https://example.com','content_hash'=>'hash-1','content_length'=>100,'content_type'=>'text/html','captured_at'=>now(),'status'=>'active']);
        SourceRetrievalRun::create(['source_id'=>$source->id,'status'=>'completed','http_status'=>200,'content_type'=>'text/html','content_length'=>100,'content_hash'=>'hash-1','snapshot_id'=>$snapshot->id,'started_at'=>now()->subMinute(),'completed_at'=>now()]);
        $summary=(new InstituteSourceVerificationReadinessService)->summarize($batch);
        $this->assertTrue($summary['source_verification_ready']);
        $this->assertSame(0,$summary['blocking_total']);
        $this->assertFalse($summary['mutation_performed']);
    }


    public function test_successful_source_verification_summary_is_read_only(): void
    {
        $source = Source::create(['source_type' => 'official', 'name' => 'Read Only Source', 'url' => 'https://example.test/readonly', 'status' => 'active']);
        $batch = ImportBatch::create(['batch_key' => 'readonly-source-' . uniqid(), 'entity_type' => 'institution', 'source_type' => 'official', 'source_id' => $source->id, 'status' => 'validated', 'total_rows' => 1, 'valid_rows' => 1, 'invalid_rows' => 0, 'candidate_rows' => 1]);
        $beforeStatus = $batch->status;

        $summary = (new InstituteSourceVerificationReadinessService)->summarize($batch);

        $this->assertTrue($summary['source_verification_ready']);
        $this->assertFalse($summary['mutation_performed']);
        $this->assertSame($beforeStatus, $batch->fresh()->status);
    }
}

<?php
namespace Tests\Feature;
use App\Models\Assertion;
use App\Models\Evidence;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportBatch;
use App\Models\ImportConflict;
use App\Models\ImportRow;
use App\Models\Source;
use App\Services\InstituteImportVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ImportVerificationGateTest extends TestCase
{
    use RefreshDatabase;
    private function batch(string $key): array
    {
        $source=Source::create(['source_type'=>'official','name'=>'Example Source','url'=>'https://example.com','status'=>'active']);
        $batch=ImportBatch::create(['batch_key'=>$key,'entity_type'=>'institution','source_type'=>'official','source_id'=>$source->id,'status'=>'validated','total_rows'=>1,'valid_rows'=>1,'invalid_rows'=>0,'candidate_rows'=>1]);
        $row=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>2,'status'=>'candidate','match_state'=>'unresolved','payload'=>['name'=>'Example Institute'],'normalized_payload'=>['name'=>'Example Institute']]);
        return [$batch,$row];
    }
    private function evidenceFor(Source $source, ImportRow $row): void
    {
        $evidence=Evidence::create(['source_id'=>$source->id,'reference'=>'https://example.com/institute','excerpt'=>'Official institute record','content_hash'=>'hash-'.$row->id,'observed_at'=>now(),'status'=>'active']);
        Assertion::create(['evidence_id'=>$evidence->id,'subject_type'=>ImportRow::class,'subject_id'=>$row->id,'attribute'=>'source_record','value'=>'official','verification_state'=>'observed','confidence'=>1]);
    }

    public function test_unverified_batch_is_blocked_without_mutation(): void
    {
        [$batch,$row]=$this->batch('batch-test-1');
        $before=[$batch->fresh()->status,$row->fresh()->status];
        $summary=(new InstituteImportVerificationService)->summarize($batch->fresh());
        $this->assertFalse($summary['verification_ready']);
        $this->assertSame(1,$summary['blocking_findings']['candidates_without_approved_identity']);
        $this->assertFalse($summary['mutation_performed']);
        $this->assertSame($before,[$batch->fresh()->status,$row->fresh()->status]);
    }
    public function test_candidate_without_active_evidence_is_blocked(): void
    {
        [$batch,$row]=$this->batch('batch-test-evidence-block');
        IdentityResolutionDecision::create(['import_row_id'=>$row->id,'institution_id'=>null,'decision'=>'approved_new','actor_type'=>'user','actor_id'=>1,'reason'=>'Approved new canonical institution.','decided_at'=>now()]);
        $summary=(new InstituteImportVerificationService)->summarize($batch->fresh());
        $this->assertFalse($summary['verification_ready']);
        $this->assertSame(1,$summary['blocking_findings']['candidate_rows_without_active_evidence']);
    }

    public function test_verified_new_candidate_is_ready(): void
    {
        [$batch,$row]=$this->batch('batch-test-2');
        $this->evidenceFor($batch->source, $row);
        IdentityResolutionDecision::create(['import_row_id'=>$row->id,'institution_id'=>null,'decision'=>'approved_new','actor_type'=>'user','actor_id'=>1,'reason'=>'Approved new canonical institution.','decided_at'=>now()]);
        $summary=(new InstituteImportVerificationService)->summarize($batch->fresh());
        $this->assertTrue($summary['verification_ready']);
        $this->assertSame(0,$summary['blocking_total']);
        $this->assertSame(1,$summary['verification']['eligible_candidate_rows']);
    }
    public function test_open_conflict_blocks_candidate(): void
    {
        [$batch,$row]=$this->batch('batch-test-3');
        IdentityResolutionDecision::create(['import_row_id'=>$row->id,'institution_id'=>null,'decision'=>'approved_new','actor_type'=>'user','actor_id'=>1,'reason'=>'Approved after identity review.','decided_at'=>now()]);
        ImportConflict::create(['import_row_id'=>$row->id,'import_batch_id'=>$batch->id,'conflict_type'=>'cross_source_attribute_difference','severity'=>'high','status'=>'open','description'=>'Open review required.','details'=>['field'=>'name']]);
        $summary=(new InstituteImportVerificationService)->summarize($batch->fresh());
        $this->assertFalse($summary['verification_ready']);
        $this->assertSame(1,$summary['blocking_findings']['candidate_rows_with_open_conflicts']);
        $this->assertFalse($summary['mutation_performed']);
    }

    public function test_missing_active_evidence_blocks_candidate_verification(): void
    {
        [$batch, $row] = $this->batch('batch-missing-evidence-gate');
        IdentityResolutionDecision::create(['import_row_id' => $row->id, 'institution_id' => null, 'decision' => 'approved_new', 'actor_type' => 'user', 'actor_id' => 1, 'reason' => 'Explicit review', 'decided_at' => now()]);
        $summary = (new InstituteImportVerificationService)->summarize($batch->fresh());
        $this->assertFalse($summary['verification_ready']);
        $this->assertSame(1, $summary['blocking_findings']['candidate_rows_without_active_evidence']);
        $this->assertFalse($summary['mutation_performed']);
    }
}

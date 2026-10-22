<?php
namespace Tests\Feature;
use App\Models\IdentityMatchCandidate;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Services\ImportReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ImportReconciliationTest extends TestCase {
 use RefreshDatabase;
 private function batch(array $rows): array {
  $b=ImportBatch::create(['batch_key'=>uniqid('reconcile-'),'entity_type'=>'institution','source_type'=>'primary','status'=>'validated']);
  $out=[]; foreach($rows as $n=>$payload)$out[]=ImportRow::create(['import_batch_id'=>$b->id,'row_number'=>$n+1,'status'=>'candidate','match_state'=>$payload['match_state']??'unresolved','payload'=>$payload,'normalized_payload'=>$payload]);
  return [$b,$out];
 }
 public function test_multiple_matches_become_conflict(): void {
  [$b,$rows]=$this->batch([['name'=>'Example']]); $a=Institution::create(['name'=>'Example A','slug'=>'example-a','status'=>'active']); $c=Institution::create(['name'=>'Example B','slug'=>'example-b','status'=>'active']);
  foreach([$a,$c] as $i) IdentityMatchCandidate::create(['import_row_id'=>$rows[0]->id,'institution_id'=>$i->id,'match_type'=>'exact_name','status'=>'candidate','reason'=>'test']);
  app(ImportReconciliationService::class)->reconcile($b);
  $this->assertSame('conflict',$rows[0]->fresh()->match_state); $this->assertDatabaseHas('import_reconciliation_events',['event_type'=>'multiple_identity_matches']);
 }
 public function test_new_candidate_is_ready_for_explicit_review(): void {
  [$b,$rows]=$this->batch([['name'=>'New','match_state'=>'new_candidate']]); app(ImportReconciliationService::class)->reconcile($b);
  $this->assertSame('new_candidate',$rows[0]->fresh()->match_state); $this->assertSame('reconciled',$b->fresh()->status);
 }

    public function test_reconciliation_does_not_promote_candidate_rows(): void
 {
  [$batch, $rows] = $this->batch([['name' => 'Review Needed', 'match_state' => 'new_candidate']]);
  app(ImportReconciliationService::class)->reconcile($batch);
  $this->assertSame('candidate', $rows[0]->fresh()->status);
  $this->assertDatabaseMissing('institutions', ['slug' => 'review-needed']);
 }
}
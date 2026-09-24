<?php
namespace App\Services;
use App\Models\DataQualityEvent;
use App\Models\IdentityMatchCandidate;
use App\Models\ImportBatch;
use App\Models\ImportReconciliationEvent;
use App\Models\ImportRow;
class ImportReconciliationService {
 public function reconcile(ImportBatch $batch): array {
  $stats=['duplicate'=>0,'conflict'=>0,'unresolved'=>0,'ready'=>0];
  foreach($batch->rows()->get() as $row){
   if($row->status==='invalid'){ $stats['conflict']++; $this->event($batch,$row,'validation_conflict','error','Validation errors prevent reconciliation.'); continue; }
   $matches=IdentityMatchCandidate::where('import_row_id',$row->id)->where('status','candidate')->get();
   if($matches->count()>1){ $stats['duplicate']++; $row->update(['match_state'=>'conflict']); $this->event($batch,$row,'multiple_identity_matches','error','Multiple deterministic identity candidates require review.'); continue; }
   if($matches->count()===1){ $row->update(['match_state'=>'matched']); $stats['ready']++; continue; }
   if($row->match_state==='new_candidate'){ $stats['ready']++; continue; }
   $row->update(['match_state'=>'unresolved']); $stats['unresolved']++;
  }
  $batch->update(['status'=>'reconciled','notes'=>json_encode($stats)]);
  return $stats;
 }
 private function event(ImportBatch $batch,ImportRow $row,string $type,string $severity,string $description): void {
  ImportReconciliationEvent::create(['import_batch_id'=>$batch->id,'import_row_id'=>$row->id,'event_type'=>$type,'state'=>'open','severity'=>$severity,'subject_type'=>$row->canonical_subject_type,'subject_id'=>$row->canonical_subject_id,'description'=>$description,'metadata'=>['match_state'=>$row->match_state],'occurred_at'=>now()]);
  DataQualityEvent::create(['subject_type'=>ImportRow::class,'subject_id'=>$row->id,'dimension'=>'import_reconciliation','state'=>'open','severity'=>$severity,'description'=>$description,'observed_at'=>now()]);
 }
}
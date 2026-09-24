<?php
namespace App\Services;
use App\Models\ImportBatch;
class ImportBatchReconciliationReport {
 public function build(ImportBatch $batch): array {
  $counts=$batch->rows()->selectRaw('status, match_state, count(*) as total')->groupBy('status','match_state')->get();
  $summary=['received'=>0,'invalid'=>0,'candidate'=>0,'matched'=>0,'new_candidate'=>0,'conflict'=>0,'promoted'=>0,'rejected'=>0,'unresolved'=>0,'ready'=>0];
  foreach($counts as $row){
   $summary[$row->status]=($summary[$row->status]??0)+(int)$row->total;
   $summary[$row->match_state]=($summary[$row->match_state]??0)+(int)$row->total;
  }
  $summary['ready']=($summary['matched']??0)+($summary['new_candidate']??0);
  return $summary;
 }
}
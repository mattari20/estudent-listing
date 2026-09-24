<?php
namespace App\Services;
use App\Models\ImportBatch;
class ImportQualityReportService {
 public function build(ImportBatch $batch): array {
  $total=(int)$batch->total_rows;
  $invalid=(int)$batch->rows()->where('status','invalid')->count();
  $candidates=(int)$batch->rows()->where('status','candidate')->count();
  $promoted=(int)$batch->rows()->where('status','promoted')->count();
  $rejected=(int)$batch->rows()->where('status','rejected')->count();
  $openConflicts=(int)$batch->rows()->where('match_state','conflict')->count();
  return ['total_rows'=>$total,'invalid_rows'=>$invalid,'candidate_rows'=>$candidates,'promoted_rows'=>$promoted,'rejected_rows'=>$rejected,'open_conflict_rows'=>$openConflicts,'conflict_rate'=>$total>0?round($openConflicts/$total,4):0.0,'candidate_rate'=>$total>0?round($candidates/$total,4):0.0];
 }
}
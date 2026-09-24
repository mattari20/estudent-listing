<?php
namespace App\Services;
use App\Models\DataReverificationQueue;
use App\Models\Evidence;
class ReverificationQueueService {
 public function enqueueEvidence(Evidence $evidence,string $reason='stale_evidence',string $priority='normal'): DataReverificationQueue {
  return DataReverificationQueue::firstOrCreate(['subject_type'=>Evidence::class,'subject_id'=>$evidence->id,'attribute'=>null,'status'=>'open'],['reason'=>$reason,'priority'=>$priority,'source_id'=>$evidence->source_id,'evidence_id'=>$evidence->id,'observed_at'=>now()]);
 }
}
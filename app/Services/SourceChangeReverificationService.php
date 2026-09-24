<?php
namespace App\Services;
use App\Models\SourceChangeAssertionLink;
use App\Models\DataReverificationQueue;
class SourceChangeReverificationService {
 public function enqueue(SourceChangeAssertionLink $link): DataReverificationQueue {
  $a=$link->assertion;
  return DataReverificationQueue::firstOrCreate(['subject_type'=>$a->subject_type,'subject_id'=>$a->subject_id,'attribute'=>$a->attribute,'status'=>'open'],['reason'=>'monitored_source_changed','priority'=>$link->impact==='high'?'high':'normal','source_id'=>$a->evidence?->source_id,'evidence_id'=>$a->evidence_id,'observed_at'=>now(),'notes'=>$link->reason]);
 }
}
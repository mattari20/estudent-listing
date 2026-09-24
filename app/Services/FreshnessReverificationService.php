<?php
namespace App\Services;
use App\Models\Evidence;
class FreshnessReverificationService {
 public function evaluate(Evidence $evidence): string {
  $state=app(SourceFreshnessService::class)->recordQuality($evidence);
  if($state==='stale') app(ReverificationQueueService::class)->enqueueEvidence($evidence,'stale_evidence','high');
  elseif($state==='aging') app(ReverificationQueueService::class)->enqueueEvidence($evidence,'aging_evidence','normal');
  return $state;
 }
}
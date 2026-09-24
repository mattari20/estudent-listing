<?php
namespace App\Services;
use App\Models\ImportPromotionEvent;
use App\Models\ImportRow;
class ImportPromotionAuditService {
 public function record(ImportRow $row,string $eventType,?int $actorId=null,?string $reason=null,array $metadata=[],?string $decisionType=null,?int $decisionId=null): ImportPromotionEvent {
  $row->loadMissing('batch');
  return ImportPromotionEvent::create([
   'import_row_id'=>$row->id,'import_batch_id'=>$row->batch->id,'entity_type'=>$row->batch->entity_type,'event_type'=>$eventType,
   'previous_status'=>$metadata['previous_status']??null,'new_status'=>$metadata['new_status']??$row->status,
   'previous_match_state'=>$metadata['previous_match_state']??null,'new_match_state'=>$metadata['new_match_state']??$row->match_state,
   'canonical_subject_type'=>$row->canonical_subject_type,'canonical_subject_id'=>$row->canonical_subject_id,
   'actor_type'=>$actorId ? 'user' : 'system','actor_id'=>$actorId,'decision_type'=>$decisionType,'decision_id'=>$decisionId,
   'reason'=>$reason,'metadata'=>$metadata,'occurred_at'=>now(),
  ]);
 }
}
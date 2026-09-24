<?php
namespace App\Services;
use App\Models\DataReverificationQueue;
use App\Models\Evidence;
use App\Models\ReverificationEvent;
use Illuminate\Support\Facades\DB;
use RuntimeException;
class ReverificationWorkflowService {
 public function start(DataReverificationQueue $item,int $actorId,string $reason): ReverificationEvent {
  return DB::transaction(function() use($item,$actorId,$reason){
   $item=DataReverificationQueue::lockForUpdate()->findOrFail($item->id);
   if($item->status!=='open') throw new RuntimeException('Only open reverification items can be started.');
   $previous=$item->status; $item->update(['status'=>'in_review','assigned_to_type'=>'user','assigned_to_id'=>$actorId]);
   return $this->event($item,'review_started',$actorId,$previous,$item->status,$reason);
  });
 }
 public function resolve(DataReverificationQueue $item,int $actorId,string $reason,?Evidence $evidence=null): ReverificationEvent {
  return DB::transaction(function() use($item,$actorId,$reason,$evidence){
   $item=DataReverificationQueue::lockForUpdate()->findOrFail($item->id);
   if(!in_array($item->status,['open','in_review'],true)) throw new RuntimeException('Only open or in-review items can be resolved.');
   $previous=$item->status; $item->update(['status'=>'resolved','resolved_at'=>now(),'evidence_id'=>$evidence?->id,'notes'=>$reason]);
   return $this->event($item,'review_resolved',$actorId,$previous,$item->status,$reason,$evidence?->id);
  });
 }
 public function supersede(DataReverificationQueue $item,int $actorId,string $reason): ReverificationEvent {
  return DB::transaction(function() use($item,$actorId,$reason){
   $item=DataReverificationQueue::lockForUpdate()->findOrFail($item->id);
   $previous=$item->status; $item->update(['status'=>'superseded','resolved_at'=>now(),'notes'=>$reason]);
   return $this->event($item,'review_superseded',$actorId,$previous,$item->status,$reason);
  });
 }
 private function event(DataReverificationQueue $item,string $type,int $actorId,string $previous,string $new,string $reason,?int $evidenceId=null): ReverificationEvent {
  return ReverificationEvent::create(['queue_id'=>$item->id,'event_type'=>$type,'actor_type'=>'user','actor_id'=>$actorId,'previous_status'=>$previous,'new_status'=>$new,'evidence_id'=>$evidenceId,'reason'=>$reason,'metadata'=>[],'occurred_at'=>now()]);
 }
}
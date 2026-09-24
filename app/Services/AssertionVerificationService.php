<?php
namespace App\Services;
use App\Models\Assertion;
use App\Models\ProvenanceReviewEvent;
use App\Models\VerificationDecision;
use Illuminate\Support\Facades\DB;
use RuntimeException;
class AssertionVerificationService {
 public function decide(Assertion $assertion,string $newState,?int $actorId,?string $reason): VerificationDecision {
  $allowed=['discovered','submitted','under_review','corroborated','verified','conditionally_accepted','conflicted','stale','superseded','rejected','withdrawn'];
  if(!in_array($newState,$allowed,true)) throw new RuntimeException('Invalid verification state.');
  return DB::transaction(function() use($assertion,$newState,$actorId,$reason){
   $previous=$assertion->verification_state;
   $decision=VerificationDecision::create(['assertion_id'=>$assertion->id,'previous_state'=>$previous,'new_state'=>$newState,'actor_type'=>$actorId?'user':null,'actor_id'=>$actorId,'reason'=>$reason,'decided_at'=>now()]);
   $assertion->update(['verification_state'=>$newState,'verified_at'=>in_array($newState,['verified','conditionally_accepted'],true)?now():null]);
   ProvenanceReviewEvent::create(['subject_type'=>Assertion::class,'subject_id'=>$assertion->id,'event_type'=>'verification_state_changed','actor_type'=>$actorId?'user':null,'actor_id'=>$actorId,'status'=>'recorded','reason'=>$reason,'metadata'=>['previous_state'=>$previous,'new_state'=>$newState],'occurred_at'=>now()]);
   return $decision;
  });
 }
}
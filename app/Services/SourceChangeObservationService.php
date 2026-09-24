<?php
namespace App\Services;
use App\Models\SourceChangeObservation;
use App\Models\SourceSnapshot;
class SourceChangeObservationService {
 public function compare(SourceSnapshot $current): SourceChangeObservation {
  $previous=SourceSnapshot::where('source_id',$current->source_id)->where('id','!=',$current->id)->latest('captured_at')->first();
  if(!$previous) return SourceChangeObservation::create(['source_id'=>$current->source_id,'current_snapshot_id'=>$current->id,'change_state'=>'baseline','materiality'=>'unknown','status'=>'detected','summary'=>'Initial snapshot captured; no previous snapshot exists.','observed_at'=>now()]);
  $same=$previous->content_hash!==null && $current->content_hash!==null && hash_equals($previous->content_hash,$current->content_hash);
  return SourceChangeObservation::create(['source_id'=>$current->source_id,'previous_snapshot_id'=>$previous->id,'current_snapshot_id'=>$current->id,'change_state'=>$same?'unchanged':'changed','materiality'=>$same?'none':'unknown','status'=>'detected','summary'=>$same?'Snapshot content hash is unchanged.':'Snapshot content hash changed; materiality requires classification.','observed_at'=>now()]);
 }
}
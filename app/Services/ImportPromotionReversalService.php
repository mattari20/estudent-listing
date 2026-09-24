<?php
namespace App\Services;
use App\Models\ImportPromotionEvent;
use App\Models\ImportRow;
use App\Models\Institution;
use Illuminate\Support\Facades\DB;
use RuntimeException;
class ImportPromotionReversalService {
 public function reverse(ImportRow $row,int $actorId,string $reason): ImportPromotionEvent {
  return DB::transaction(function() use($row,$actorId,$reason) {
   $row=ImportRow::query()->lockForUpdate()->with('batch')->findOrFail($row->id);
   if($row->status!=='promoted') throw new RuntimeException('Only promoted import rows can be reversed.');
   if(!$row->canonical_subject_type || !$row->canonical_subject_id) throw new RuntimeException('No canonical subject is recorded for this promotion.');
   $events=ImportPromotionEvent::where('import_row_id',$row->id)->orderByDesc('id')->get();
   $success=$events->firstWhere('event_type','promotion_succeeded');
   $metadata=$success?->metadata ?? [];
   $createdByThisImport=(bool)($metadata['created_canonical']??false);
   if($row->canonical_subject_type===Institution::class && $createdByThisImport){
    $institution=Institution::lockForUpdate()->findOrFail($row->canonical_subject_id);
    if($institution->campuses()->exists() || $institution->programs()->exists() || $institution->accreditations()->exists() || $institution->externalRankings()->exists() || $institution->recommendations()->exists()){
     throw new RuntimeException('The newly created institution has dependent canonical records; governed lifecycle supersession is required instead of deletion.');
    }
    $institution->update(['status'=>'superseded']);
   }
   $previousStatus=$row->status; $previousMatch=$row->match_state;
   $row->update(['status'=>'reversed','match_state'=>'reversed']);
   return app(ImportPromotionAuditService::class)->record($row,'promotion_reversed',$actorId,$reason,[
    'previous_status'=>$previousStatus,'previous_match_state'=>$previousMatch,'reversal_mode'=>$createdByThisImport?'canonical_superseded_or_unlinked':'import_row_only','canonical_preserved'=>true,
   ]);
  });
 }
}
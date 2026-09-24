<?php
namespace App\Services;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportPromotionDecision;
use App\Models\ImportRow;
use Illuminate\Support\Facades\DB;
class BulkImportPromotionService {
 public function promote(array $rowIds,int $actorId): array {
  $result=['promoted'=>0,'skipped'=>0,'failed'=>0];
  foreach($rowIds as $id){
   try { DB::transaction(function() use($id,$actorId,&$result): void {
    $row=ImportRow::query()->lockForUpdate()->with('batch')->find($id);
    if(!$row || $row->status!=='candidate'){$result['skipped']++;return;}\n    if(ImportConflict::where('import_row_id',$row->id)->where('status','open')->exists()){ $result['skipped']++; return; }
    $type=$row->batch->entity_type;
    if($type==='institution'){
     $decision=IdentityResolutionDecision::query()->where('import_row_id',$row->id)->whereIn('decision',['approved_new','approved_match'])->latest('id')->first();
     if(!$decision){$result['skipped']++;return;}
     app(ImportPromotionAuditService::class)->record($row,'promotion_started',$actorId,'Explicitly approved promotion started.',['previous_status'=>$row->status,'previous_match_state'=>$row->match_state],'identity_resolution',$decision->id);
     app(InstituteImportPromoter::class)->promote($row,$decision);\n     if($decision->decision==='approved_match') app(ImportChangeDetectionService::class)->detect($row);
    } else {
     $decision=ImportPromotionDecision::query()->where('import_row_id',$row->id)->where('decision','approved')->latest('id')->first();
     if(!$decision){$result['skipped']++;return;}
     app(ImportPromotionAuditService::class)->record($row,'promotion_started',$actorId,'Explicitly approved promotion started.',['previous_status'=>$row->status,'previous_match_state'=>$row->match_state],'import_promotion',$decision->id);
     app(EnrichmentPromotionService::class)->validateParent($row);
     $previousStatus=$row->status; $previousMatch=$row->match_state;
     $entity=app(EnrichmentPromotionService::class)->promote($row);
     app(ImportPromotionAuditService::class)->record($row,'promotion_succeeded',$actorId,'Enrichment promoted to canonical data.',['previous_status'=>$previousStatus,'previous_match_state'=>$previousMatch,'created_canonical'=>false,'canonical_class'=>$entity::class],'import_promotion',$decision->id);
    }
    $result['promoted']++;
   }); } catch(\Throwable $e) {
    $result['failed']++;
    $failedRow=ImportRow::with('batch')->find($id);
    if($failedRow) app(ImportPromotionAuditService::class)->record($failedRow,'promotion_failed',$actorId,$e->getMessage(),['error_class'=>$e::class]);
   }
  }
  return $result;
 }
}
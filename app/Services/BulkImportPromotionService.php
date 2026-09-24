<?php
namespace App\Services;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportRow;
use Illuminate\Support\Facades\DB;
class BulkImportPromotionService {
 public function promote(array $rowIds, int $actorId): array {
  $result=['promoted'=>0,'skipped'=>0,'failed'=>0];
  foreach($rowIds as $id){
   try { DB::transaction(function() use($id,&$result): void {
    $row=ImportRow::query()->lockForUpdate()->with('batch')->find($id);
    if(!$row || $row->status!=='candidate'){$result['skipped']++;return;}
    $type=$row->batch->entity_type;
    if($type==='institution'){
     $decision=IdentityResolutionDecision::query()->where('import_row_id',$row->id)->whereIn('decision',['approved_new','approved_match'])->latest('id')->first();
     if(!$decision){$result['skipped']++;return;}
     app(InstituteImportPromoter::class)->promote($row,$decision);
    } else {
     app(EnrichmentPromotionService::class)->validateParent($row);
     app(EnrichmentPromotionService::class)->promote($row);
    }
    $result['promoted']++;
   }); } catch (\Throwable $e) { $result['failed']++; }
  }
  return $result;
 }
}
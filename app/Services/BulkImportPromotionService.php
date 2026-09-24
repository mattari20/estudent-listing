<?php
namespace App\Services;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportRow;
use Illuminate\Support\Facades\DB;
class BulkImportPromotionService {
 public function promote(array $rowIds, int $actorId): array {
  $result=['promoted'=>0,'skipped'=>0,'failed'=>0];
  foreach($rowIds as $id){
   try {
    DB::transaction(function() use($id,$actorId,&$result): void {
     $row=ImportRow::query()->lockForUpdate()->find($id);
     if(!$row){$result['skipped']++;return;}
     $decision=IdentityResolutionDecision::query()->where('import_row_id',$row->id)->whereIn('decision',['approved_new','approved_match'])->latest('id')->first();
     if(!$decision || $row->status!=='candidate'){$result['skipped']++;return;}
     app(InstituteImportPromoter::class)->promote($row,$decision);
     $result['promoted']++;
    });
   } catch (\Throwable $e) { $result['failed']++; }
  }
  return $result;
 }
}
<?php
namespace App\Http\Controllers;

use App\Models\IdentityResolutionDecision;
use App\Models\ImportBatch;
use App\Models\ImportConflict;
use App\Models\ImportPromotionDecision;
use App\Models\ImportRow;
use App\Services\BulkImportPromotionService;
use App\Services\EnrichmentPromotionService;
use App\Services\ImportPromotionAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstituteBulkPromotionController {
 public function store(Request $request, BulkImportPromotionService $service): RedirectResponse {
  $data=$request->validate(['row_ids'=>'required|array|max:100','row_ids.*'=>'integer|exists:import_rows,id']);
  $result=$service->promote(array_values(array_unique($data['row_ids'])),$request->user()->id);
  return back()->with('status',"Bulk promotion completed: {$result['promoted']} promoted, {$result['skipped']} skipped, {$result['failed']} failed.");
 }

 public function bulkDecision(Request $request, EnrichmentPromotionService $enrichment, ImportPromotionAuditService $audit): RedirectResponse {
  $data=$request->validate([
   'row_ids'=>'required|array|min:1|max:100',
   'row_ids.*'=>'integer|distinct|exists:import_rows,id',
   'decision'=>'required|in:approve,reject',
   'reason'=>'required|string|max:2000',
  ]);
  $result=['approved'=>0,'rejected'=>0,'skipped'=>0,'failed'=>0];
  foreach(array_values(array_unique($data['row_ids'])) as $id) {
   try {
    DB::transaction(function () use ($id,$data,$request,$enrichment,$audit,&$result): void {
     $row=ImportRow::query()->lockForUpdate()->with('batch')->find($id);
     if(!$row || !in_array($row->status,['candidate','validated','duplicate','invalid'],true)) { $result['skipped']++; return; }
     if(ImportConflict::where('import_row_id',$row->id)->where('status','open')->exists()) { $result['skipped']++; return; }
     if($data['decision']==='approve') {
      if($row->batch?->entity_type==='institution' || !in_array($row->batch?->entity_type,['campus','program','program_offering','admission_intake','eligibility_rule','merit_record','selection_outcome','fee_record','scholarship','accreditation'],true) || $row->status!=='candidate') { $result['skipped']++; return; }
      $enrichment->validateParent($row);
      $decision=ImportPromotionDecision::create([
       'import_row_id'=>$row->id,'decision'=>'approved','actor_type'=>'user','actor_id'=>$request->user()->id,
       'reason'=>$data['reason'],'decided_at'=>now(),
      ]);
      $audit->record($row,'promotion_approved',$request->user()->id,$data['reason'],[
       'decision'=>'approved','operator_action'=>'bulk_approve','bulk'=>true,
      ],'import_promotion',$decision->id);
      $result['approved']++;
      return;
     }
     if($row->batch?->entity_type==='institution') {
      $decision=IdentityResolutionDecision::create([
       'import_row_id'=>$row->id,'institution_id'=>null,'decision'=>'rejected','actor_type'=>'user',
       'actor_id'=>$request->user()->id,'reason'=>$data['reason'],'decided_at'=>now(),
      ]);
      $type='identity_resolution';
     } else {
      $decision=ImportPromotionDecision::create([
       'import_row_id'=>$row->id,'decision'=>'rejected','actor_type'=>'user','actor_id'=>$request->user()->id,
       'reason'=>$data['reason'],'decided_at'=>now(),
      ]);
      $type='import_promotion';
     }
     $previousStatus=$row->status;
     $note=trim((string)$row->notes);
     $row->update(['status'=>'rejected','notes'=>trim($note.($note!=='' ? "\n" : '').'Rejected by operator: '.$data['reason'])]);
     $audit->record($row,'promotion_rejected',$request->user()->id,$data['reason'],[
      'previous_status'=>$previousStatus,'new_status'=>'rejected','operator_action'=>'bulk_reject','bulk'=>true,
     ],$type,$decision->id);
     $result['rejected']++;
    });
   } catch (\Throwable $e) {
    $result['failed']++;
   }
  }
  return back()->with('status',"Bulk review complete: {$result['approved']} approved, {$result['rejected']} rejected, {$result['skipped']} skipped (not eligible/conflict), {$result['failed']} failed.");
 }

 public function approveAndPromoteNew(Request $request, ImportBatch $importBatch, BulkImportPromotionService $service): RedirectResponse
 {
  $data=$request->validate(['reason'=>['required','string','max:2000']]);
  $result=$service->approveAndPromoteNew($importBatch->id,$request->user()->id,$data['reason']);
  return redirect()->route('institutes.operations.queue',['batch_id'=>$importBatch->id])->with('status',"Bulk new-institution approval completed: {$result['promoted']} promoted, {$result['skipped']} skipped, {$result['matched_or_conflicted']} held for review, {$result['failed']} failed.");
 }
}
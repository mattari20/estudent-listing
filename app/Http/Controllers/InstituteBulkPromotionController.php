<?php
namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\BulkImportPromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class InstituteBulkPromotionController {
 public function store(Request $request, BulkImportPromotionService $service): RedirectResponse {
  $data=$request->validate(['row_ids'=>'required|array|max:100','row_ids.*'=>'integer|exists:import_rows,id']);
  $result=$service->promote(array_values(array_unique($data['row_ids'])),$request->user()->id);
  return back()->with('status',"Bulk promotion completed: {$result['promoted']} promoted, {$result['skipped']} skipped, {$result['failed']} failed.");
 }

 public function approveAndPromoteNew(Request $request, ImportBatch $importBatch, BulkImportPromotionService $service): RedirectResponse
 {
  $data=$request->validate(['reason'=>['required','string','max:2000']]);
  $result=$service->approveAndPromoteNew($importBatch->id,$request->user()->id,$data['reason']);
  return redirect()->route('institutes.operations.queue',['batch_id'=>$importBatch->id])->with('status',"Bulk new-institution approval completed: {$result['promoted']} promoted, {$result['skipped']} skipped, {$result['matched_or_conflicted']} held for review, {$result['failed']} failed.");
 }
}
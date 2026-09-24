<?php
namespace App\Http\Controllers;
use App\Services\BulkImportPromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class InstituteBulkPromotionController {
 public function store(Request $request, BulkImportPromotionService $service): RedirectResponse {
  $data=$request->validate(['row_ids'=>'required|array|max:100','row_ids.*'=>'integer|exists:import_rows,id']);
  $result=$service->promote(array_values(array_unique($data['row_ids'])),$request->user()->id);
  return back()->with('status',"Bulk promotion completed: {$result['promoted']} promoted, {$result['skipped']} skipped, {$result['failed']} failed.");
 }
}
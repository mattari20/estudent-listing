<?php
namespace App\Http\Controllers;
use App\Models\IdentityMatchCandidate;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Services\ImportBatchReconciliationReport;
use Illuminate\Http\Request;
use Illuminate\View\View;
class InstituteOperationsController {
 public function queue(Request $request, ImportBatchReconciliationReport $report): View {
  $batchId=$request->integer('batch_id');
  $batches=ImportBatch::query()->with('source')->latest()->paginate(20,['*'],'batches_page');
  $rows=ImportRow::query()->whereIn('status',['candidate','validated'])->with(['batch','batch.source'])->when($batchId,fn($q)=>$q->where('import_batch_id',$batchId))->latest()->paginate(30,['*'],'rows_page')->withQueryString();
  $summary=$batchId?$report->build(ImportBatch::findOrFail($batchId)):null;
  return view('institutes.operations.queue',compact('rows','batches','summary','batchId'));
 }
 public function show(ImportRow $importRow): View {
  $importRow->load(['batch.source']);
  $matches=IdentityMatchCandidate::query()->where('import_row_id',$importRow->id)->with('institution')->get();
  return view('institutes.operations.row',compact('importRow','matches'));
 }
}
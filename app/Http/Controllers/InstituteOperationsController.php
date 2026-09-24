<?php
namespace App\Http\Controllers;
use App\Models\IdentityMatchCandidate;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportPromotionDecision;
use App\Models\ImportPromotionEvent;
use App\Models\ImportRow;
use App\Services\ImportBatchReconciliationReport;
use App\Services\ImportDependencyContextService;
use Illuminate\Http\Request;
use Illuminate\View\View;
class InstituteOperationsController {
 public function queue(Request $request, ImportBatchReconciliationReport $report, ImportQualityReportService $quality): View {
  $batchId=$request->integer('batch_id'); $batches=\App\Models\ImportBatch::query()->with('source')->latest()->paginate(20,['*'],'batches_page');
  $rows=ImportRow::query()->whereIn('status',['candidate','validated'])->with(['batch','batch.source'])->when($batchId,fn($q)=>$q->where('import_batch_id',$batchId))->latest()->paginate(30,['*'],'rows_page')->withQueryString();
  $summary=$batchId?$report->build(\App\Models\ImportBatch::findOrFail($batchId)):null; return view('institutes.operations.queue',compact('rows','batches','summary','batchId'));
 }
 public function show(ImportRow $importRow, ImportDependencyContextService $context): View {
  $importRow->load(['batch.source']); $matches=IdentityMatchCandidate::query()->where('import_row_id',$importRow->id)->with('institution')->get();
  $identityDecision=IdentityResolutionDecision::query()->where('import_row_id',$importRow->id)->latest('id')->first();
  $enrichmentDecision=ImportPromotionDecision::query()->where('import_row_id',$importRow->id)->latest('id')->first();
  $history=ImportPromotionEvent::query()->where('import_row_id',$importRow->id)->latest('id')->limit(50)->get();\n  $conflicts=ImportConflict::query()->where('import_row_id',$importRow->id)->latest('id')->get();
  $dependency=$context->build($importRow);
  return view('institutes.operations.row',compact('importRow','matches','identityDecision','enrichmentDecision','dependency','history','conflicts'));
 }
}
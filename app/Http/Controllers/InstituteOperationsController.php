<?php
namespace App\Http\Controllers;
use App\Models\IdentityMatchCandidate;
use App\Models\Institution;
use App\Models\ImportPromotionDecision;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportPromotionDecision;
use App\Models\ImportPromotionEvent;
use App\Models\ImportRow;
use App\Models\ImportConflict;
use App\Models\DataReverificationQueue;
use App\Services\ImportBatchReconciliationReport;
use App\Services\ImportDependencyContextService;
use App\Services\ImportQualityReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
class InstituteOperationsController {
 public function queue(Request $request, ImportBatchReconciliationReport $report, ImportQualityReportService $quality): View {
  $batchId=$request->integer('batch_id');
  $batches=\App\Models\ImportBatch::query()->with('source')->withCount([
   'rows as pending_rows_count' => fn($q) => $q->whereIn('status',['candidate','validated']),
   'rows as promoted_rows_count' => fn($q) => $q->where('status','promoted'),
   'rows as rejected_rows_count' => fn($q) => $q->where('status','rejected'),
   'rows as conflict_rows_count' => fn($q) => $q->where('match_state','conflict'),
  ])->latest()->paginate(20,['*'],'batches_page');
  $approvedCounts=IdentityResolutionDecision::query()
   ->whereIn('decision',['approved_new','approved_match'])
   ->selectRaw('import_row_id')
   ->with('importRow:id,import_batch_id')
   ->get()
   ->groupBy(fn($decision) => $decision->importRow?->import_batch_id)
   ->map(fn($items) => $items->count());
  $batchParentInstitutions = [];
  foreach ($batches as $batch) {
   $batch->approved_rows_count=$approvedCounts->get($batch->id,0);
   if ($batch->entity_type !== 'institution' && preg_match('/parent_institution_id=(\\d+)/', (string) $batch->notes, $match)) {
    $batchParentInstitutions[$batch->id] = Institution::query()->find((int) $match[1]);
   }
  }
  $rows=ImportRow::query()->whereIn('status',['candidate','validated','duplicate','invalid'])->with(['batch','batch.source'])->when($batchId,fn($q)=>$q->where('import_batch_id',$batchId))->latest()->paginate(30,['*'],'rows_page')->withQueryString();
  $rowIds = $rows->getCollection()->pluck('id');
  $decisions = ImportPromotionDecision::query()->whereIn('import_row_id',$rowIds)->latest('id')->get()->unique('import_row_id')->keyBy('import_row_id');
  $identityDecisions = IdentityResolutionDecision::query()->whereIn('import_row_id',$rowIds)->latest('id')->get()->unique('import_row_id')->keyBy('import_row_id');
  $rowDecisionMap = [];
  foreach ($rows as $row) {
   $rowDecisionMap[$row->id] = $row->batch?->entity_type === 'institution'
    ? $identityDecisions->get($row->id)?->decision
    : $decisions->get($row->id)?->decision;
  }
  $summary=$batchId?$report->build(\App\Models\ImportBatch::findOrFail($batchId)):null;
  $qualityReport=$batchId?$quality->build(\App\Models\ImportBatch::findOrFail($batchId)):null;
  $reverification=DataReverificationQueue::query()->where('status','open')->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")->orderBy('due_at')->limit(20)->get();
  return view('institutes.operations.queue',compact('rows','batches','summary','qualityReport','batchId','reverification','batchParentInstitutions','rowDecisionMap'));
 }
 public function show(ImportRow $importRow, ImportDependencyContextService $context): View {
  $importRow->load(['batch.source']);
  $matches=IdentityMatchCandidate::query()->where('import_row_id',$importRow->id)->with('institution')->get();
  $identityDecision=IdentityResolutionDecision::query()->where('import_row_id',$importRow->id)->latest('id')->first();
  $enrichmentDecision=ImportPromotionDecision::query()->where('import_row_id',$importRow->id)->latest('id')->first();
  $history=ImportPromotionEvent::query()->where('import_row_id',$importRow->id)->latest('id')->limit(50)->get();
  $conflicts=ImportConflict::query()->where('import_row_id',$importRow->id)->latest('id')->get();
  $dependency=$context->build($importRow);
  $parentInstitution = ($importRow->parent_subject_type === Institution::class && $importRow->parent_subject_id)
   ? Institution::query()->find($importRow->parent_subject_id)
   : null;
  return view('institutes.operations.row',compact('importRow','matches','identityDecision','enrichmentDecision','dependency','history','conflicts','parentInstitution'));
 }
}
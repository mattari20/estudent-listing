<?php
namespace App\Http\Controllers;
use App\Models\IdentityResolutionDecision;
use App\Models\IdentityMatchCandidate;
use App\Models\ImportRow;
use App\Models\DataReverificationQueue;
use App\Models\Institution;
use App\Services\EnrichmentPromotionService;
use App\Services\ImportPromotionAuditService;
use App\Services\ImportConflictLifecycleService;
use App\Services\ImportPromotionReversalService;
use App\Services\InstituteIdentityResolver;
use App\Services\IdentityResolutionDecisionService;
use App\Services\InstituteImportPromoter;
use App\Services\ImportCrossSourceComparisonService;
use App\Services\ImportChangeDetectionService;
use App\Services\ReverificationWorkflowService;
use App\Services\CanonicalRefreshService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
class InstituteImportPromotionController {
 public function quickApprove(Request $request, ImportRow $importRow, EnrichmentPromotionService $service, \App\Services\OperatorTransitionService $transitions): RedirectResponse {
  $importRow->load('batch');
  if($importRow->batch?->entity_type==='institution'){
   return back()->withErrors([
    'approval'=>'Quick approval is disabled for institution identity rows. Review identity candidates and conflicts, then record an explicit approved-new or approved-match decision before promotion.',
   ]);
  }
  $reason='Quick approval from the import operations review queue.';
  $transitions->recordPromotionDecision($importRow, (int) $request->user()->id, 'approved', $reason, 'quick_approve', $service);
  return back()->with('status', 'Import row '.$importRow->row_number.' approved. It is now ready for promotion.');
 }
 public function reject(Request $request, ImportRow $importRow, \App\Services\OperatorTransitionService $transitions): RedirectResponse {
  $data=$request->validate(['reason'=>['required', 'string', 'max:2000']]);
  $transitions->rejectRow($importRow, (int) $request->user()->id, $data['reason']);
  return redirect()->route('institutes.operations.queue', ['batch_id'=>$importRow->import_batch_id])->with('status', 'Import row '.$importRow->row_number.' was rejected and removed from the active review queue.');
 }
 public function resolve(ImportRow $importRow, InstituteIdentityResolver $resolver): RedirectResponse { abort_unless($importRow->status==='candidate', 409); $resolver->resolve($importRow); return back()->with('status', 'Identity candidates refreshed.'); }
 public function decide(Request $request, ImportRow $importRow, IdentityResolutionDecisionService $decisions): RedirectResponse {
  $data=$request->validate(['decision'=>['required', 'in:approved_new,approved_match,rejected,conflict'], 'institution_id'=>['nullable', 'integer', 'exists:institutions,id'], 'reason'=>['required', 'string', 'max:2000']]);
  if($data['decision']==='approved_match' && empty($data['institution_id'])) return back()->withErrors(['institution_id'=>'An institution is required for an approved match.']);
  if($data['decision']==='approved_new' && !empty($data['institution_id'])) return back()->withErrors(['institution_id'=>'New approval cannot target an existing institution.']);
  $decisions->record($importRow, (int) $request->user()->id, $data['decision'], isset($data['institution_id']) ? (int) $data['institution_id'] : null, $data['reason']);
  return back()->with('status', 'Resolution decision recorded. Repeated identical requests reuse the same immutable decision.');
 }
 public function enrichmentDecision(Request $request, ImportRow $importRow, EnrichmentPromotionService $service, \App\Services\OperatorTransitionService $transitions): RedirectResponse {
  $data=$request->validate(['decision'=>['required', 'in:approved,rejected,conflict'], 'reason'=>['required', 'string', 'max:2000']]);
  $transitions->recordPromotionDecision($importRow, (int) $request->user()->id, $data['decision'], $data['reason'], 'enrichment_decision', $service);
  return back()->with('status', 'Enrichment promotion decision recorded.');
 }
 public function resolveConflict(Request $request, ImportRow $importRow, ImportConflictLifecycleService $lifecycle): RedirectResponse {
  $data=$request->validate(['conflict_id'=>['required', 'integer', 'exists:import_conflicts,id'], 'reason'=>['required', 'string', 'max:2000']]);
  try {
   $lifecycle->resolve($importRow, (int) $data['conflict_id'], (int) $request->user()->id, $data['reason']);
  } catch (RuntimeException $exception) {
   return back()->withErrors(['conflict'=>$exception->getMessage()]);
  }
  return back()->with('status', 'Import conflict resolved with an explicit operator reason and immutable history recorded.');
 }
 public function promote(Request $request, ImportRow $importRow, InstituteImportPromoter $promoter, ImportCrossSourceComparisonService $comparison, ImportChangeDetectionService $changes): RedirectResponse {
  try {
   abort_unless($importRow->batch?->entity_type==='institution', 409);
   $decision=IdentityResolutionDecision::query()->where('import_row_id', $importRow->id)->latest('id')->first();
   if ($decision === null || !in_array($decision->decision, ['approved_new', 'approved_match'], true)) return back()->withErrors(['promotion'=>'The latest identity decision must be explicitly approved before promoting this institution.']);
   if($decision->decision==='approved_match'){
    $institution=Institution::findOrFail($decision->institution_id);
    if($comparison->compareInstitution($importRow, $institution)>0) return back()->withErrors(['promotion'=>'Canonical data differs from the import source; resolve the resulting conflict before promotion.']);
   }
   $institution=$promoter->promote($importRow, $decision);
   if($decision->decision==='approved_match') $changes->detect($importRow);
   return redirect()->route('institutes.operations.row', $importRow)->with('status', 'Canonical institution promoted: '.$institution->name);
  } catch (\Throwable $exception) {
   report($exception);
   return back()->withErrors(['promotion'=>$exception instanceof \RuntimeException ? $exception->getMessage() : 'Promotion failed unexpectedly. The error has been logged; no success was recorded.']);
  }
 }
 public function publishInstitution(Request $request, ImportRow $importRow, \App\Services\OperatorTransitionService $transitions): RedirectResponse {
  try {
   $institution = $transitions->publishInstitution($importRow, (int) $request->user()->id);
   return back()->with('status', $institution->wasChanged('status') ? 'Institution published. It should now appear in the public directory.' : 'Institution is already published in the directory.');
  } catch (RuntimeException $exception) {
   return back()->withErrors(['publication'=>$exception->getMessage()]);
  }
 }
 public function captureRowEvidence(Request $request, ImportRow $importRow, \App\Services\InstituteSourceEvidenceCaptureService $service): RedirectResponse {
  abort_unless($importRow->batch?->entity_type==='institution' && $importRow->status==='candidate', 409);
  $data=$request->validate([
   'reference'=>['required', 'url', 'max:2048'],
   'excerpt'=>['required', 'string', 'min:20', 'max:10000'],
  ]);
  $source=\App\Models\Source::create([
   'source_type'=>'official_institutional',
   'name'=>'Official source evidence for institution import row '.$importRow->id,
   'url'=>$data['reference'],
   'status'=>'active',
  ]);
  $evidence=$service->capture($source, $data['reference'], $data['excerpt'], null, [[
   'subject_type'=>ImportRow::class,
   'subject_id'=>$importRow->id,
   'attribute'=>'institution_identity',
   'value'=>(string) (($importRow->normalized_payload ?? $importRow->payload)['official_name'] ?? ($importRow->normalized_payload ?? $importRow->payload)['name'] ?? ''),
   'verification_state'=>'operator_observed',
   'verified_at'=>now()->toDateTimeString(),
  ]]);
  app(ImportPromotionAuditService::class)->record($importRow, 'source_evidence_captured', $request->user()->id, 'Operator captured official source evidence.', ['evidence_id'=>$evidence->id, 'reference'=>$data['reference']], 'source_evidence', $evidence->id);
  return back()->with('status', 'Source evidence recorded. You may now retry promotion after checking the identity decision and conflicts.');
 }
 public function enrichmentPromote(Request $request, ImportRow $importRow, EnrichmentPromotionService $service, \App\Services\OperatorTransitionService $transitions): RedirectResponse {
  try {
   $transitions->promoteEnrichment($importRow, (int) $request->user()->id, $service);
  } catch (\Throwable $e) {
   return back()->withErrors(['promotion'=>$e->getMessage()]);
  }
  return redirect()->route('institutes.operations.row', $importRow)->with('status', 'Canonical enrichment promoted.');
 }
 public function geographyResolve(Request $request, ImportRow $importRow, \App\Services\GeographyImportResolverService $resolver): RedirectResponse {
  abort_unless($importRow->batch && $importRow->batch->entity_type==='geography', 409);
  $resolver->resolve($importRow);
  return back()->with('status', 'Geography candidates refreshed.');
 }
 public function geographyPromote(Request $request, ImportRow $importRow, \App\Services\GeographyPromotionService $service, \App\Services\OperatorTransitionService $transitions): RedirectResponse {
  try {
   $entity=$transitions->promoteGeography($importRow, (int) $request->user()->id, $service);
  } catch (\Throwable $e) {
   return back()->withErrors(['promotion'=>$e->getMessage()]);
  }
  return back()->with('status', 'Canonical geography promoted: '.$entity->name);
 }
 public function reverse(Request $request, ImportRow $importRow, ImportPromotionReversalService $reversal): RedirectResponse {
  $data=$request->validate(['reason'=>['required', 'string', 'max:2000']]);
  try { $reversal->reverse($importRow, $request->user()->id, $data['reason']); return back()->with('status', 'Promotion reversed safely; canonical history was preserved.'); } catch(\Throwable $e){ return back()->withErrors(['reversal'=>$e->getMessage()]); }
 }
 public function startReverification(Request $request, DataReverificationQueue $item, ReverificationWorkflowService $workflow): RedirectResponse {
  $data=$request->validate(['reason'=>['required', 'string', 'max:2000']]); $workflow->start($item, $request->user()->id, $data['reason']); return back()->with('status', 'Reverification review started.');
 }
 public function resolveReverification(Request $request, DataReverificationQueue $item, ReverificationWorkflowService $workflow): RedirectResponse {
  $data=$request->validate(['reason'=>['required', 'string', 'max:2000'], 'evidence_id'=>['nullable', 'integer', 'exists:evidences,id']]); $evidence=$data['evidence_id']?\App\Models\Evidence::findOrFail($data['evidence_id']):null; $workflow->resolve($item, $request->user()->id, $data['reason'], $evidence); return back()->with('status', 'Reverification item resolved.');
 }
 public function refreshCanonical(Request $request, DataReverificationQueue $item, CanonicalRefreshService $refresh): RedirectResponse {
  $data=$request->validate(['reason'=>['required', 'string', 'max:2000'], 'name'=>['nullable', 'string', 'max:255'], 'official_name'=>['nullable', 'string', 'max:255'], 'short_name'=>['nullable', 'string', 'max:255'], 'institution_type'=>['nullable', 'string', 'max:255'], 'status'=>['nullable', 'string', 'max:50']]);
  $attributes=array_filter(collect($data)->except('reason')->all(), fn($v)=>$v!==null && $v!=='');
  try { $institution=$refresh->refreshInstitution($item, $request->user()->id, $attributes, $data['reason']); return back()->with('status', 'Canonical data refreshed for '.$institution->name.'.'); } catch(\Throwable $e){ return back()->withErrors(['refresh'=>$e->getMessage()]); }
 }
}
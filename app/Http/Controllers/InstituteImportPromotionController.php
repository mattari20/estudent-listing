<?php
namespace App\Http\Controllers;
use App\Models\IdentityResolutionDecision;
use App\Models\IdentityMatchCandidate;
use App\Models\ImportPromotionDecision;
use App\Models\ImportRow;
use App\Models\ImportConflict;
use App\Models\DataReverificationQueue;
use App\Models\Institution;
use App\Services\EnrichmentPromotionService;
use App\Services\ImportPromotionAuditService;
use App\Services\ImportPromotionReversalService;
use App\Services\InstituteIdentityResolver;
use App\Services\InstituteImportPromoter;
use App\Services\ImportCrossSourceComparisonService;
use App\Services\ImportChangeDetectionService;
use App\Services\ReverificationWorkflowService;
use App\Services\CanonicalRefreshService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class InstituteImportPromotionController {
 public function quickApprove(Request $request, ImportRow $importRow, EnrichmentPromotionService $service, ImportPromotionAuditService $audit): RedirectResponse {
  abort_unless($importRow->status==='candidate',409);
  $importRow->load('batch');

  // Institution identity is high risk: never infer "new institution" or select
  // the highest-scoring identity candidate from a one-click action. Require the
  // operator to review and record an explicit identity-resolution decision.
  if($importRow->batch?->entity_type==='institution'){
   return back()->withErrors([
    'approval'=>'Quick approval is disabled for institution identity rows. Review identity candidates and conflicts, then record an explicit approved-new or approved-match decision before promotion.',
   ]);
  }

  $service->validateParent($importRow);
  $reason='Quick approval from the import operations review queue.';
  $decision=ImportPromotionDecision::create([
   'import_row_id'=>$importRow->id,
   'decision'=>'approved',
   'actor_type'=>'user',
   'actor_id'=>$request->user()->id,
   'reason'=>$reason,
   'decided_at'=>now(),
  ]);
  $audit->record($importRow,'promotion_approved',$request->user()->id,$reason,[
   'operator_action'=>'quick_approve',
   'decision'=>'approved',
  ],'import_promotion',$decision->id);

  return back()->with('status','Import row '.$importRow->row_number.' approved. It is now ready for promotion.');
 }
 public function reject(Request $request, ImportRow $importRow, ImportPromotionAuditService $audit): RedirectResponse {
  abort_unless(in_array($importRow->status, ['candidate','validated','duplicate','invalid'], true), 409);
  $data=$request->validate(['reason'=>['required','string','max:2000']]);
  $previousStatus=$importRow->status;
  $previousMatchState=$importRow->match_state;
  if($importRow->batch?->entity_type==='institution'){
   $decision=IdentityResolutionDecision::create(['import_row_id'=>$importRow->id,'institution_id'=>null,'decision'=>'rejected','actor_type'=>'user','actor_id'=>$request->user()->id,'reason'=>$data['reason'],'decided_at'=>now()]);
   $decisionType='identity_resolution';
  } else {
   $decision=ImportPromotionDecision::create(['import_row_id'=>$importRow->id,'decision'=>'rejected','actor_type'=>'user','actor_id'=>$request->user()->id,'reason'=>$data['reason'],'decided_at'=>now()]);
   $decisionType='import_promotion';
  }
  $note=trim((string) $importRow->notes);
  $rejectionNote='Rejected by operator: '.$data['reason'];
  $importRow->update([
   'status'=>'rejected',
   'notes'=>trim($note.($note!=='' ? "\n" : '').$rejectionNote),
  ]);
  $audit->record($importRow,'promotion_rejected',$request->user()->id,$data['reason'],[
   'previous_status'=>$previousStatus,
   'new_status'=>'rejected',
   'previous_match_state'=>$previousMatchState,
   'new_match_state'=>$importRow->match_state,
   'operator_action'=>'reject_import_row',
  ],$decisionType,$decision->id);
  return redirect()->route('institutes.operations.queue',['batch_id'=>$importRow->import_batch_id])->with('status','Import row '.$importRow->row_number.' was rejected and removed from the active review queue.');
 }
 public function resolve(ImportRow $importRow, InstituteIdentityResolver $resolver): RedirectResponse { abort_unless($importRow->status==='candidate',409); $resolver->resolve($importRow); return back()->with('status','Identity candidates refreshed.'); }
 public function decide(Request $request, ImportRow $importRow, ImportPromotionAuditService $audit): RedirectResponse {
  $data=$request->validate(['decision'=>['required','in:approved_new,approved_match,rejected,conflict'],'institution_id'=>['nullable','integer','exists:institutions,id'],'reason'=>['required','string','max:2000']]);
  if($data['decision']==='approved_match' && empty($data['institution_id'])) return back()->withErrors(['institution_id'=>'An institution is required for an approved match.']);
  if($data['decision']==='approved_new' && !empty($data['institution_id'])) return back()->withErrors(['institution_id'=>'New approval cannot target an existing institution.']);
  $decision=IdentityResolutionDecision::create(['import_row_id'=>$importRow->id,'institution_id'=>$data['institution_id']??null,'decision'=>$data['decision'],'actor_type'=>'user','actor_id'=>$request->user()->id,'reason'=>$data['reason'],'decided_at'=>now()]);
  $audit->record($importRow,$data['decision']==='rejected'?'promotion_rejected':'promotion_approved',$request->user()->id,$data['reason'],['decision'=>$data['decision']],'identity_resolution',$decision->id);
  return back()->with('status','Resolution decision recorded.');
 }
 public function enrichmentDecision(Request $request, ImportRow $importRow, EnrichmentPromotionService $service, ImportPromotionAuditService $audit): RedirectResponse {
  abort_unless($importRow->status==='candidate',409); $service->validateParent($importRow);
  $data=$request->validate(['decision'=>['required','in:approved,rejected,conflict'],'reason'=>['required','string','max:2000']]);
  $decision=ImportPromotionDecision::create(['import_row_id'=>$importRow->id,'decision'=>$data['decision'],'actor_type'=>'user','actor_id'=>$request->user()->id,'reason'=>$data['reason'],'decided_at'=>now()]);
  $audit->record($importRow,$data['decision']==='approved'?'promotion_approved':'promotion_rejected',$request->user()->id,$data['reason'],['decision'=>$data['decision']],'import_promotion',$decision->id);
  return back()->with('status','Enrichment promotion decision recorded.');
 }
 public function resolveConflict(Request $request, ImportRow $importRow): RedirectResponse {
  $data=$request->validate(['conflict_id'=>['required','integer','exists:import_conflicts,id'],'reason'=>['required','string','max:2000']]);
  $conflict=ImportConflict::query()->whereKey($data['conflict_id'])->where('import_row_id',$importRow->id)->firstOrFail();
  $conflict->update(['status'=>'resolved','resolved_by_type'=>'user','resolved_by_id'=>$request->user()->id,'resolution_reason'=>$data['reason'],'resolved_at'=>now()]);
  if(!ImportConflict::where('import_row_id',$importRow->id)->where('status','open')->exists() && $importRow->match_state==='conflict') $importRow->update(['match_state'=>IdentityResolutionDecision::where('import_row_id',$importRow->id)->exists()?'matched':'new_candidate']);
  return back()->with('status','Import conflict resolved with an explicit operator reason.');
 }
 public function promote(Request $request, ImportRow $importRow, InstituteImportPromoter $promoter, ImportCrossSourceComparisonService $comparison, ImportChangeDetectionService $changes): RedirectResponse {
  try {
   abort_unless($importRow->batch?->entity_type==='institution', 409);
   $decision=IdentityResolutionDecision::query()->where('import_row_id',$importRow->id)->whereIn('decision',['approved_new','approved_match'])->latest('id')->first();
   if ($decision === null) return back()->withErrors(['promotion'=>'Record an approved identity decision before promoting this institution.']);
   if($decision->decision==='approved_match'){
    $institution=Institution::findOrFail($decision->institution_id);
    if($comparison->compareInstitution($importRow,$institution)>0) return back()->withErrors(['promotion'=>'Canonical data differs from the import source; resolve the resulting conflict before promotion.']);
   }
   $institution=$promoter->promote($importRow,$decision);
   if($decision->decision==='approved_match') $changes->detect($importRow);
   return redirect()->route('institutes.operations.row',$importRow)->with('status','Canonical institution promoted: '.$institution->name);
  } catch (\Throwable $exception) {
   report($exception);
   return back()->withErrors(['promotion'=>$exception instanceof \RuntimeException ? $exception->getMessage() : 'Promotion failed unexpectedly. The error has been logged; no success was recorded.']);
  }
 }
 public function publishInstitution(Request $request, ImportRow $importRow, ImportPromotionAuditService $audit): RedirectResponse {
  abort_unless($importRow->batch?->entity_type === 'institution', 409);
  abort_unless($importRow->status === 'promoted', 409);
  abort_unless($importRow->canonical_subject_type === Institution::class && $importRow->canonical_subject_id, 409);

  $institution = Institution::query()->findOrFail($importRow->canonical_subject_id);
  if ($institution->status === 'active') {
   return back()->with('status', 'Institution is already published in the directory.');
  }

  $decision = IdentityResolutionDecision::query()
   ->where('import_row_id', $importRow->id)
   ->whereIn('decision', ['approved_new', 'approved_match'])
   ->latest('id')->firstOrFail();

  $hasActiveEvidence = \App\Models\Assertion::query()
   ->where('subject_type', ImportRow::class)
   ->where('subject_id', $importRow->id)
   ->whereHas('evidence', fn ($query) => $query->where('status', 'active'))
   ->exists();
  abort_unless($hasActiveEvidence, 409, 'Active official source evidence is required before publication.');
  abort_unless(!ImportConflict::where('import_row_id', $importRow->id)->where('status', 'open')->exists(), 409, 'Resolve all open import conflicts before publication.');

  $previousStatus = $institution->status;
  $institution->update(['status' => 'active', 'verified_at' => now()]);
  $audit->record(
   $importRow,
   'institution_published',
   $request->user()->id,
   'Operator explicitly published the promoted institution to the public directory.',
   ['institution_id' => $institution->id, 'previous_status' => $previousStatus, 'new_status' => 'active'],
   'identity_resolution',
   $decision->id
  );

  return redirect()->route('institutes.operations.row', $importRow)->with('status', 'Institution published. It should now appear in the public directory.');
 }
 public function captureRowEvidence(Request $request, ImportRow $importRow, \App\Services\InstituteSourceEvidenceCaptureService $service): RedirectResponse {
  abort_unless($importRow->batch?->entity_type==='institution' && $importRow->status==='candidate', 409);
  $data=$request->validate([
   'reference'=>['required','url','max:2048'],
   'excerpt'=>['required','string','min:20','max:10000'],
  ]);
  $source=\App\Models\Source::create([
   'source_type'=>'official_institutional',
   'name'=>'Official source evidence for institution import row '.$importRow->id,
   'url'=>$data['reference'],
   'status'=>'active',
  ]);
  $evidence=$service->capture($source,$data['reference'],$data['excerpt'],null,[[
   'subject_type'=>ImportRow::class,
   'subject_id'=>$importRow->id,
   'attribute'=>'institution_identity',
   'value'=>(string) (($importRow->normalized_payload ?? $importRow->payload)['official_name'] ?? ($importRow->normalized_payload ?? $importRow->payload)['name'] ?? ''),
   'verification_state'=>'operator_observed',
   'verified_at'=>now()->toDateTimeString(),
  ]]);
  app(ImportPromotionAuditService::class)->record($importRow,'source_evidence_captured',$request->user()->id,'Operator captured official source evidence.',['evidence_id'=>$evidence->id,'reference'=>$data['reference']],'source_evidence',$evidence->id);
  return back()->with('status','Source evidence recorded. You may now retry promotion after checking the identity decision and conflicts.');
 }
 public function enrichmentPromote(Request $request, ImportRow $importRow, EnrichmentPromotionService $service, ImportPromotionAuditService $audit): RedirectResponse {
  abort_unless($importRow->status==='candidate',409); $decision=ImportPromotionDecision::query()->where('import_row_id',$importRow->id)->where('decision','approved')->latest('id')->firstOrFail();
  $service->validateParent($importRow); $previousStatus=$importRow->status; $previousMatch=$importRow->match_state;
  try { $entity=$service->promote($importRow); } catch (\Throwable $e) { return back()->withErrors(['promotion'=>$e->getMessage()]); }
  $audit->record($importRow,'promotion_succeeded',$request->user()->id,'Enrichment promoted to canonical data.',['previous_status'=>$previousStatus,'previous_match_state'=>$previousMatch,'created_canonical'=>false,'canonical_class'=>$entity::class],'import_promotion',$decision->id);
  return redirect()->route('institutes.operations.row',$importRow)->with('status','Canonical enrichment promoted.');
 }
 public function geographyResolve(Request $request, ImportRow $importRow, \App\Services\GeographyImportResolverService $resolver): RedirectResponse {
  abort_unless($importRow->batch && $importRow->batch->entity_type==='geography',409);
  $resolver->resolve($importRow);
  return back()->with('status','Geography candidates refreshed.');
 }
 public function geographyPromote(Request $request, ImportRow $importRow, \App\Services\GeographyPromotionService $service, ImportPromotionAuditService $audit): RedirectResponse {
  abort_unless($importRow->batch && $importRow->batch->entity_type==='geography',409);
  abort_unless($importRow->status==='candidate',409);
  $decision=ImportPromotionDecision::query()->where('import_row_id',$importRow->id)->where('decision','approved')->latest('id')->firstOrFail();
  $entity=$service->promote($importRow);
  $audit->record($importRow,'promotion_succeeded',$request->user()->id,'Geography promoted to canonical data.',['canonical_id'=>$entity->id,'canonical_class'=>$entity::class],'geography_promotion',$decision->id);
  return back()->with('status','Canonical geography promoted: '.$entity->name);
 }
 public function reverse(Request $request, ImportRow $importRow, ImportPromotionReversalService $reversal): RedirectResponse {
  $data=$request->validate(['reason'=>['required','string','max:2000']]);
  try { $reversal->reverse($importRow,$request->user()->id,$data['reason']); return back()->with('status','Promotion reversed safely; canonical history was preserved.'); } catch(\Throwable $e){ return back()->withErrors(['reversal'=>$e->getMessage()]); }
 }
 public function startReverification(Request $request, DataReverificationQueue $item, ReverificationWorkflowService $workflow): RedirectResponse {
  $data=$request->validate(['reason'=>['required','string','max:2000']]); $workflow->start($item,$request->user()->id,$data['reason']); return back()->with('status','Reverification review started.');
 }
 public function resolveReverification(Request $request, DataReverificationQueue $item, ReverificationWorkflowService $workflow): RedirectResponse {
  $data=$request->validate(['reason'=>['required','string','max:2000'],'evidence_id'=>['nullable','integer','exists:evidences,id']]); $evidence=$data['evidence_id']?\App\Models\Evidence::findOrFail($data['evidence_id']):null; $workflow->resolve($item,$request->user()->id,$data['reason'],$evidence); return back()->with('status','Reverification item resolved.');
 }
 public function refreshCanonical(Request $request, DataReverificationQueue $item, CanonicalRefreshService $refresh): RedirectResponse {
  $data=$request->validate(['reason'=>['required','string','max:2000'],'name'=>['nullable','string','max:255'],'official_name'=>['nullable','string','max:255'],'short_name'=>['nullable','string','max:255'],'institution_type'=>['nullable','string','max:255'],'status'=>['nullable','string','max:50']]);
  $attributes=array_filter(collect($data)->except('reason')->all(),fn($v)=>$v!==null && $v!=='');
  try { $institution=$refresh->refreshInstitution($item,$request->user()->id,$attributes,$data['reason']); return back()->with('status','Canonical data refreshed for '.$institution->name.'.'); } catch(\Throwable $e){ return back()->withErrors(['refresh'=>$e->getMessage()]); }
 }
}
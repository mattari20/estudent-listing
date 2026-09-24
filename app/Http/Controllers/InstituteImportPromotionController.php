<?php
namespace App\Http\Controllers;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportPromotionDecision;
use App\Models\ImportRow;
use App\Services\EnrichmentPromotionService;
use App\Services\ImportPromotionAuditService;
use App\Services\ImportPromotionReversalService;
use App\Services\InstituteIdentityResolver;
use App\Services\InstituteImportPromoter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class InstituteImportPromotionController {
 public function resolve(ImportRow $importRow, InstituteIdentityResolver $resolver): RedirectResponse {
  abort_unless($importRow->status==='candidate',409); $resolver->resolve($importRow); return back()->with('status','Identity candidates refreshed.');
 }
 public function decide(Request $request, ImportRow $importRow, ImportPromotionAuditService $audit): RedirectResponse {
  $data=$request->validate(['decision'=>['required','in:approved_new,approved_match,rejected,conflict'],'institution_id'=>['nullable','integer','exists:institutions,id'],'reason'=>['required','string','max:2000']]);
  if($data['decision']==='approved_match' && empty($data['institution_id'])) return back()->withErrors(['institution_id'=>'An institution is required for an approved match.']);
  if($data['decision']==='approved_new' && !empty($data['institution_id'])) return back()->withErrors(['institution_id'=>'New approval cannot target an existing institution.']);
  $decision=IdentityResolutionDecision::create(['import_row_id'=>$importRow->id,'institution_id'=>$data['institution_id']??null,'decision'=>$data['decision'],'actor_type'=>'user','actor_id'=>$request->user()->id,'reason'=>$data['reason'],'decided_at'=>now()]);
  $audit->record($importRow,$data['decision']==='rejected'?'promotion_rejected':'promotion_approved',$request->user()->id,$data['reason'],['decision'=>$data['decision']], 'identity_resolution',$decision->id);
  return back()->with('status','Resolution decision recorded.');
 }
 public function enrichmentDecision(Request $request, ImportRow $importRow, EnrichmentPromotionService $service, ImportPromotionAuditService $audit): RedirectResponse {
  abort_unless($importRow->status==='candidate',409); $service->validateParent($importRow);
  $data=$request->validate(['decision'=>['required','in:approved,rejected,conflict'],'reason'=>['required','string','max:2000']]);
  $decision=ImportPromotionDecision::create(['import_row_id'=>$importRow->id,'decision'=>$data['decision'],'actor_type'=>'user','actor_id'=>$request->user()->id,'reason'=>$data['reason'],'decided_at'=>now()]);
  $audit->record($importRow,$data['decision']==='approved'?'promotion_approved':'promotion_rejected',$request->user()->id,$data['reason'],['decision'=>$data['decision']], 'import_promotion',$decision->id);
  return back()->with('status','Enrichment promotion decision recorded.');
 }
 public function promote(Request $request, ImportRow $importRow, InstituteImportPromoter $promoter): RedirectResponse {
  $decision=IdentityResolutionDecision::query()->where('import_row_id',$importRow->id)->whereIn('decision',['approved_new','approved_match'])->latest('id')->firstOrFail();
  $institution=$promoter->promote($importRow,$decision); return redirect()->route('institutes.operations.row',$importRow)->with('status','Canonical institution promoted: '.$institution->name);
 }
 public function enrichmentPromote(Request $request, ImportRow $importRow, EnrichmentPromotionService $service, ImportPromotionAuditService $audit): RedirectResponse {
  abort_unless($importRow->status==='candidate',409); $decision=ImportPromotionDecision::query()->where('import_row_id',$importRow->id)->where('decision','approved')->latest('id')->firstOrFail();
  $service->validateParent($importRow); $previousStatus=$importRow->status; $previousMatch=$importRow->match_state;
  $entity=$service->promote($importRow);
  $audit->record($importRow,'promotion_succeeded',$request->user()->id,'Enrichment promoted to canonical data.',[
   'previous_status'=>$previousStatus,'previous_match_state'=>$previousMatch,'created_canonical'=>false,'canonical_class'=>$entity::class
  ],'import_promotion',$decision->id);
  return redirect()->route('institutes.operations.row',$importRow)->with('status','Canonical enrichment promoted.');
 }
 public function reverse(Request $request, ImportRow $importRow, ImportPromotionReversalService $reversal): RedirectResponse {
  $data=$request->validate(['reason'=>['required','string','max:2000']]);
  try { $reversal->reverse($importRow,$request->user()->id,$data['reason']); return back()->with('status','Promotion reversed safely; canonical history was preserved.'); }
  catch(\Throwable $e){ return back()->withErrors(['reversal'=>$e->getMessage()]); }
 }
}
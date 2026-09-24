<?php
namespace App\Http\Controllers;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportRow;
use App\Services\InstituteIdentityResolver;
use App\Services\InstituteImportPromoter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class InstituteImportPromotionController {
 public function resolve(ImportRow $importRow, InstituteIdentityResolver $resolver): RedirectResponse {
  abort_unless($importRow->status==='candidate',409);
  $resolver->resolve($importRow);
  return back()->with('status','Identity candidates refreshed.');
 }
 public function decide(Request $request, ImportRow $importRow): RedirectResponse {
  $data=$request->validate(['decision'=>['required','in:approved_new,approved_match,rejected,conflict'],'institution_id'=>['nullable','integer','exists:institutions,id'],'reason'=>['required','string','max:2000']]);
  if($data['decision']==='approved_match' && empty($data['institution_id'])) return back()->withErrors(['institution_id'=>'An institution is required for an approved match.']);
  if($data['decision']==='approved_new' && !empty($data['institution_id'])) return back()->withErrors(['institution_id'=>'New approval cannot target an existing institution.']);
  IdentityResolutionDecision::create(['import_row_id'=>$importRow->id,'institution_id'=>$data['institution_id']??null,'decision'=>$data['decision'],'actor_type'=>'user','actor_id'=>$request->user()->id,'reason'=>$data['reason'],'decided_at'=>now()]);
  return back()->with('status','Resolution decision recorded. Promotion requires an approved decision.');
 }
 public function promote(Request $request, ImportRow $importRow, InstituteImportPromoter $promoter): RedirectResponse {
  $decision=IdentityResolutionDecision::query()->where('import_row_id',$importRow->id)->whereIn('decision',['approved_new','approved_match'])->latest('id')->firstOrFail();
  $institution=$promoter->promote($importRow,$decision);
  return redirect()->route('institutes.operations.row',$importRow)->with('status','Canonical institution promoted: '.$institution->name);
 }
}
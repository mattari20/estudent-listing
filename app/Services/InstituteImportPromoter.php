<?php
namespace App\Services;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\InstitutionName;
use Illuminate\Support\Str;
use RuntimeException;
class InstituteImportPromoter {
 public function promote(ImportRow $row, IdentityResolutionDecision $decision): Institution {
  if($decision->import_row_id!==$row->id) throw new RuntimeException('Resolution decision does not belong to import row.');
  if(!in_array($decision->decision,['approved_new','approved_match'],true)) throw new RuntimeException('Only an explicit approved resolution decision can promote an import row.');\n  if(ImportConflict::where('import_row_id',$row->id)->where('status','open')->exists()) throw new RuntimeException('Open import conflicts must be explicitly resolved before promotion.');
  $payload=$row->normalized_payload ?? $row->payload ?? [];
  if(trim((string)($payload['name'] ?? ''))==='') throw new RuntimeException('Institution name is required.');
  if($decision->decision==='approved_match'){
   $institution=Institution::findOrFail($decision->institution_id);
   $previousStatus=$row->status; $previousMatch=$row->match_state;
   $row->update(['status'=>'promoted','match_state'=>'matched','canonical_subject_type'=>Institution::class,'canonical_subject_id'=>$institution->id]);
   app(ImportPromotionAuditService::class)->record($row,'promotion_succeeded',$decision->actor_id,'Existing canonical institution matched.',[
    'previous_status'=>$previousStatus,'previous_match_state'=>$previousMatch,'created_canonical'=>false,'promotion_mode'=>'approved_match'
   ],'identity_resolution',$decision->id);
   return $institution;
  }
  $slug=trim((string)($payload['slug'] ?? '')) ?: Str::slug((string)$payload['name']);
  if(Institution::where('slug',$slug)->exists()) throw new RuntimeException('Institution slug already exists; explicit identity resolution is required.');
  $institution=Institution::create(['name'=>$payload['name'],'official_name'=>$payload['official_name']??null,'short_name'=>$payload['short_name']??null,'slug'=>$slug,'institution_type'=>$payload['institution_type']??null,'status'=>$payload['status']??'candidate']);
  InstitutionName::create(['institution_id'=>$institution->id,'name'=>$institution->name,'name_type'=>'official','is_current'=>true]);
  $previousStatus=$row->status; $previousMatch=$row->match_state;
  $row->update(['status'=>'promoted','match_state'=>'new_canonical','canonical_subject_type'=>Institution::class,'canonical_subject_id'=>$institution->id]);
  app(ImportPromotionAuditService::class)->record($row,'promotion_succeeded',$decision->actor_id,'New canonical institution created.',[
   'previous_status'=>$previousStatus,'previous_match_state'=>$previousMatch,'created_canonical'=>true,'promotion_mode'=>'approved_new'
  ],'identity_resolution',$decision->id);
  return $institution;
 }
}
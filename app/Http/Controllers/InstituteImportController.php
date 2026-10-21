<?php
namespace App\Http\Controllers;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Source;
use App\Models\Assertion;
use App\Services\ImportSchemaProfileService;
use App\Services\InstituteImportControllerSupport;
use App\Services\InstituteIdentityResolver;
use App\Services\InstituteSourceEvidenceCaptureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
class InstituteImportController {
 public function create(ImportSchemaProfileService $profiles): View { $profile=$profiles->institution(); return view('institutes.import',compact('profile')); }
 public function template(ImportSchemaProfileService $profiles) {
  $profile=$profiles->institution(); return response($profiles->template($profile),200,['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="institution-import-template-v'.$profile->version.'.csv"']);
 }
 public function store(Request $request,ImportSchemaProfileService $profiles,InstituteImportControllerSupport $support,InstituteIdentityResolver $identityResolver,InstituteSourceEvidenceCaptureService $evidenceCapture): View {
  $validated=$request->validate(['source_name'=>['required','string','max:255'],'source_type'=>['required','string','max:100'],'source_url'=>['nullable','url','max:2048'],'entity_type'=>['required','in:institution'],'schema_version'=>['required','string','max:20'],'csv'=>['required','file','mimes:csv,txt','max:5120']]);
  $profile=$profiles->institution($validated['schema_version']);
  $source=Source::create(['source_type'=>$validated['source_type'],'name'=>$validated['source_name'],'url'=>$validated['source_url']??null,'status'=>'active']);
  $batch=ImportBatch::create(['batch_key'=>(string)Str::uuid(),'entity_type'=>'institution','source_type'=>$validated['source_type'],'source_id'=>$source->id,'status'=>'processing','received_at'=>now(),'notes'=>'schema_profile='.$profile->profile_key.'@'.$profile->version]);
  $handle=fopen($request->file('csv')->getRealPath(),'r'); $header=fgetcsv($handle); $rowNumber=1; $valid=0; $invalid=0; $candidate=0; $seen=[]; $pending=[];
  $headerErrors=$header?$profiles->validateHeaders($header,$profile):['CSV header is missing.'];
  if($headerErrors){ fclose($handle); $batch->update(['status'=>'failed','completed_at'=>now(),'notes'=>implode('; ',$headerErrors)]); return view('institutes.import-result',compact('batch')); }
  $header=array_map(fn($v)=>trim((string)$v),$header);
  while(($data=fgetcsv($handle))!==false){
   $rowNumber++; $payload=[]; foreach($header as $i=>$key) $payload[$key]=isset($data[$i])?trim((string)$data[$i]):null;
   $normalized=$support->normalize($payload); $rowErrors=$support->validateRow($normalized,$seen); $slug=(string)($normalized['slug']??''); if($slug!=='')$seen[$slug]=true;
   $status=$rowErrors?'invalid':'candidate'; if($rowErrors)$invalid++;else{$valid++;$candidate++;}
   $pending[]=['import_batch_id'=>$batch->id,'row_number'=>$rowNumber,'status'=>$status,'match_state'=>'unresolved','payload'=>json_encode($payload),'normalized_payload'=>json_encode($normalized),'validation_errors'=>$rowErrors?json_encode($rowErrors):null,'created_at'=>now(),'updated_at'=>now()];
   if(count($pending)>=500){$support->insertRows($pending);$pending=[];}
  }
  fclose($handle); if($pending)$support->insertRows($pending);
  $batch->update(['status'=>'validated','total_rows'=>$rowNumber-1,'valid_rows'=>$valid,'invalid_rows'=>$invalid,'candidate_rows'=>$candidate,'completed_at'=>now()]);
  $batch->rows()->where('status','candidate')->orderBy('id')->chunkById(200,function($rows) use ($identityResolver): void {
   foreach($rows as $row) $identityResolver->resolve($row);
  });
  // Supplied official evidence is recorded automatically during import. Evidence never approves,
  // promotes, or publishes an institution; identity decisions remain a separate governed step.
  $captured=0;
  $batch->rows()->where('status','candidate')->orderBy('id')->chunkById(200,function($rows) use ($evidenceCapture,&$captured): void {
   foreach($rows as $row) {
    $payload=$row->normalized_payload ?? $row->payload ?? [];
    $url=trim((string)($payload['official_source_url']??''));
    $excerpt=trim((string)($payload['source_excerpt']??''));
    if($url==='' || $excerpt==='') continue;
    $name=trim((string)($payload['official_name']??$payload['name']??''));
    if($name==='') continue;
    DB::transaction(function() use ($row,$url,$excerpt,$name,$evidenceCapture,&$captured): void {
     $source=Source::create(['source_type'=>'official_institutional','name'=>'Official source evidence for institution import row '.$row->id,'url'=>$url,'status'=>'active']);
     $evidenceCapture->capture($source,$url,$excerpt,null,[[
      'subject_type'=>ImportRow::class,'subject_id'=>$row->id,'attribute'=>'institution_identity','value'=>$name,
      'verification_state'=>'operator_supplied','verified_at'=>now()->toDateTimeString(),
     ]]);
     $captured++;
    });
   }
  });
  $batch->update(['notes'=>trim((string)$batch->notes.'; evidence_captured='.$captured)]);
  return view('institutes.import-result',compact('batch'));
 }
}
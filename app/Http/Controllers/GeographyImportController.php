<?php
namespace App\Http\Controllers;
use App\Models\ImportBatch;
use App\Models\Source;
use App\Services\GeographyImportBatchService;
use App\Services\GeographySchemaProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
class GeographyImportController {
 public function create(GeographySchemaProfileService $profiles): View { $profile=$profiles->geography(); return view('institutes.geography-import',compact('profile')); }
 public function template(GeographySchemaProfileService $profiles) {
  $profile=$profiles->geography();
  return response($profiles->template($profile),200,['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="geography-import-template-v'.$profile->version.'.csv"']);
 }
 public function store(Request $request,GeographySchemaProfileService $profiles,GeographyImportBatchService $service): View {
  $validated=$request->validate(['source_name'=>['required','string','max:255'],'source_type'=>['required','string','max:100'],'source_url'=>['nullable','url','max:2048'],'schema_version'=>['required','string','max:20'],'csv'=>['required','file','mimes:csv,txt','max:5120']]);
  $profile=$profiles->geography($validated['schema_version']);
  $source=Source::create(['source_type'=>$validated['source_type'],'name'=>$validated['source_name'],'url'=>$validated['source_url']??null,'status'=>'active']);
  $batch=ImportBatch::create(['batch_key'=>(string)Str::uuid(),'entity_type'=>'geography','source_type'=>$validated['source_type'],'source_id'=>$source->id,'status'=>'processing','received_at'=>now(),'notes'=>'schema_profile='.$profile->profile_key.'@'.$profile->version]);
  $handle=fopen($request->file('csv')->getRealPath(),'r'); $header=fgetcsv($handle);
  $headerErrors=$header?$profiles->validateHeaders($header,$profile):['CSV header is missing.'];
  if($headerErrors){fclose($handle);$batch->update(['status'=>'failed','completed_at'=>now(),'notes'=>implode('; ',$headerErrors)]);return view('institutes.geography-import-result',compact('batch'));}
  $header=array_map(fn($v)=>trim((string)$v),$header);$row=1;$valid=0;$invalid=0;$pending=[];
  while(($data=fgetcsv($handle))!==false){$row++;$payload=[];foreach($header as $i=>$key)$payload[$key]=isset($data[$i])?trim((string)$data[$i]):null;$normalized=$service->normalize($payload);$errors=$service->validate($normalized);$status=$errors?'invalid':'candidate';$errors?$invalid++:$valid++;$pending[]=['import_batch_id'=>$batch->id,'row_number'=>$row,'status'=>$status,'match_state'=>'unresolved','payload'=>json_encode($payload),'normalized_payload'=>json_encode($normalized),'validation_errors'=>$errors?json_encode($errors):null,'created_at'=>now(),'updated_at'=>now()];if(count($pending)>=500){\App\Models\ImportRow::insert($pending);$pending=[];}}
  fclose($handle);if($pending)\App\Models\ImportRow::insert($pending);
  $batch->update(['status'=>'validated','total_rows'=>$row-1,'valid_rows'=>$valid,'invalid_rows'=>$invalid,'candidate_rows'=>$valid,'completed_at'=>now()]);
  return view('institutes.geography-import-result',compact('batch'));
 }
}
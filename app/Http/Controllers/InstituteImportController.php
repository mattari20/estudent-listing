<?php
namespace App\Http\Controllers;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
class InstituteImportController {
 public function create(): View { return view('institutes.import'); }
 public function store(Request $request): View {
  $validated=$request->validate(['source_name'=>['required','string','max:255'],'source_type'=>['required','string','max:100'],'source_url'=>['nullable','url','max:2048'],'entity_type'=>['required','in:institution'],'csv'=>['required','file','mimes:csv,txt','max:5120']]);
  $source=Source::create(['source_type'=>$validated['source_type'],'name'=>$validated['source_name'],'url'=>$validated['source_url']??null,'status'=>'active']);
  $batch=ImportBatch::create(['batch_key'=>(string) Str::uuid(),'entity_type'=>$validated['entity_type'],'source_type'=>$validated['source_type'],'source_id'=>$source->id,'status'=>'processing','received_at'=>now()]);
  $handle=fopen($request->file('csv')->getRealPath(),'r');
  $header=fgetcsv($handle);
  $allowed=['name','official_name','short_name','slug','institution_type','status'];
  $errors=[]; $rowNumber=1; $valid=0; $invalid=0; $candidate=0;
  if (!$header) { fclose($handle); $batch->update(['status'=>'failed','completed_at'=>now(),'notes'=>'CSV header is missing.']); return view('institutes.import-result',compact('batch')); }
  $header=array_map(fn($v)=>trim((string)$v),$header);
  if (array_diff($header,$allowed) || !in_array('name',$header,true)) { fclose($handle); $batch->update(['status'=>'failed','completed_at'=>now(),'notes'=>'CSV must contain name and only supported institution columns.']); return view('institutes.import-result',compact('batch')); }
  while(($data=fgetcsv($handle))!==false){
   $rowNumber++; $payload=[]; foreach($header as $i=>$key){$payload[$key]=isset($data[$i])?trim((string)$data[$i]):null;}
   $rowErrors=[]; if(($payload['name']??'')==='') $rowErrors[]='name is required';
   $normalized=$payload; $normalized['name']=trim((string)$normalized['name']);
   if(empty($normalized['slug'])) $normalized['slug']=Str::slug($normalized['name']);
   $duplicate=ImportRow::query()->where('import_batch_id',$batch->id)->where('normalized_payload->slug',$normalized['slug'])->exists();
   if($duplicate) $rowErrors[]='duplicate slug within import batch';
   $status=$rowErrors?'invalid':'candidate'; if($rowErrors)$invalid++;else{$valid++;$candidate++;}
   ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>$rowNumber,'status'=>$status,'match_state'=>'unresolved','payload'=>$payload,'normalized_payload'=>$normalized,'validation_errors'=>$rowErrors?:null]);
  }
  fclose($handle); $batch->update(['status'=>'validated','total_rows'=>$rowNumber-1,'valid_rows'=>$valid,'invalid_rows'=>$invalid,'candidate_rows'=>$candidate,'completed_at'=>now()]);
  return view('institutes.import-result',compact('batch'));
 }
}
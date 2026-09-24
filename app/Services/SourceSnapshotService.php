<?php
namespace App\Services;
use App\Models\Source;
use App\Models\SourceSnapshot;
class SourceSnapshotService {
 public function capture(Source $source,string $content,?string $reference=null,?string $contentType=null,array $metadata=[]): SourceSnapshot {
  return SourceSnapshot::create(['source_id'=>$source->id,'reference'=>$reference,'content_hash'=>hash('sha256',$content),'content_length'=>strlen($content),'content_type'=>$contentType,'captured_at'=>now(),'status'=>'captured','metadata'=>$metadata]);
 }
 public function latest(Source $source): ?SourceSnapshot { return SourceSnapshot::where('source_id',$source->id)->latest('captured_at')->first(); }
}
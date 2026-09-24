<?php
namespace App\Services;
use App\Models\ChangeEvent;
use App\Models\SourceChangeAssertionLink;
class SourceChangeEventProjector {
 public function project(SourceChangeAssertionLink $link): ChangeEvent {
  $a=$link->assertion;
  return ChangeEvent::firstOrCreate(['subject_type'=>$a->subject_type,'subject_id'=>$a->subject_id,'event_type'=>'source_content_changed','detected_at'=>$link->created_at],['materiality'=>$link->impact,'actor_type'=>'system','status'=>'detected','summary'=>'A monitored source changed and may affect attribute: '.($link->attribute??'unknown').'.']);
 }
}
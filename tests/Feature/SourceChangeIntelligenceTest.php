<?php
namespace Tests\Feature;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class SourceChangeIntelligenceTest extends TestCase {
 use RefreshDatabase;
 public function test_changed_source_links_assertions_and_queues_reverification(): void {
  Http::fake(['https://example.test/*'=>Http::sequence()->push('name old',200)->push('name new',200)]);
  $source=Source::create(['source_type'=>'web','name'=>'Official','url'=>'https://example.test/page','status'=>'active']);
  $first=app(\App\Services\SourceRetrievalService::class)->retrieve($source);
  $e=\App\Models\Evidence::create(['source_id'=>$source->id,'reference'=>'test','excerpt'=>'name old','content_hash'=>$first->content_hash,'observed_at'=>now(),'status'=>'active']);
  \App\Models\Assertion::create(['evidence_id'=>$e->id,'subject_type'=>'institution','subject_id'=>1,'attribute'=>'name','value'=>'Old','verification_state'=>'verified']);
  $result=app(\App\Services\SourceMonitoringPipeline::class)->run($source);
  $this->assertSame('changed',$result['observation']->change_state);
  $this->assertSame(1,$result['intelligence']['queued']);
 }
}
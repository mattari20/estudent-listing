<?php
namespace Tests\Feature;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class SourceRetrievalTest extends TestCase {
 use RefreshDatabase;
 public function test_public_source_is_retrieved_and_snapshotted(): void {
  Http::fake(['https://example.test/*'=>Http::response('official content',200,['Content-Type'=>'text/html'])]);
  $source=Source::create(['source_type'=>'web','name'=>'Official','url'=>'https://example.test/page','status'=>'active']);
  $run=app(\App\Services\SourceRetrievalService::class)->retrieve($source);
  $this->assertSame('completed',$run->status); $this->assertNotNull($run->snapshot_id);
 }
 public function test_private_ip_source_is_rejected(): void {
  $source=Source::create(['source_type'=>'web','name'=>'Private','url'=>'http://127.0.0.1/test','status'=>'active']);
  $run=app(\App\Services\SourceRetrievalService::class)->retrieve($source);
  $this->assertSame('failed',$run->status); $this->assertNull($run->snapshot_id);
 }

    public function test_non_http_source_url_is_rejected(): void
 {
  $source=Source::create(['source_type'=>'web','name'=>'Local File','url'=>'file:///etc/passwd','status'=>'active']);
  $run=app(\App\Services\SourceRetrievalService::class)->retrieve($source);
  $this->assertSame('failed',$run->status);
  $this->assertNull($run->snapshot_id);
 }
}
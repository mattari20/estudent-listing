<?php
namespace Tests\Feature;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class SourceRetrievalTest extends TestCase {
 use RefreshDatabase;
 protected function setUp(): void {
  parent::setUp();
  $this->app->instance(\App\Services\SourceUrlGuard::class, new \App\Services\SourceUrlGuard(
   static fn (string $host, int $type): array => [['host' => $host, 'type' => 'A', 'ip' => '93.184.216.34']]
  ));
 }
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

    public function test_failed_http_response_does_not_create_snapshot(): void
 {
  Http::fake(['https://example.test/*'=>Http::response('temporary failure',503,['Content-Type'=>'text/plain'])]);
  $source=Source::create(['source_type'=>'web','name'=>'Unavailable Source','url'=>'https://example.test/unavailable','status'=>'active']);
  $run=app(\App\Services\SourceRetrievalService::class)->retrieve($source);
  $this->assertSame('failed',$run->status);
  $this->assertNull($run->snapshot_id);
  $this->assertDatabaseCount('source_snapshots',0);
 }

    public function test_failed_retrieval_keeps_source_without_creating_snapshot(): void
 {
  Http::fake(['https://example.test/*' => Http::response('temporary failure', 503)]);
  $source = Source::create(['source_type' => 'web', 'name' => 'Retry Source', 'url' => 'https://example.test/retry', 'status' => 'active']);
  $run = app(\App\Services\SourceRetrievalService::class)->retrieve($source);
  $this->assertSame('failed', $run->status);
  $this->assertNull($run->snapshot_id);
  $this->assertDatabaseHas('sources', ['id' => $source->id, 'status' => 'active']);
 }
}
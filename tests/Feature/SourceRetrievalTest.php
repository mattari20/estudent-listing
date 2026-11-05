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


 public function test_new_default_retrieval_calls_create_distinct_operations(): void {
  Http::fakeSequence()
   ->push('first official content',200,['Content-Type'=>'text/html'])
   ->push('second official content',200,['Content-Type'=>'text/html']);
  $source=Source::create(['source_type'=>'web','name'=>'Fresh Retrieval Official','url'=>'https://example.test/fresh','status'=>'active']);
  $service=app(\App\Services\SourceRetrievalService::class);
  $first=$service->retrieve($source);
  $second=$service->retrieve($source);
  $this->assertNotSame($first->id,$second->id);
  $this->assertNotSame($first->snapshot_id,$second->snapshot_id);
  $this->assertNotSame($first->content_hash,$second->content_hash);
  Http::assertSentCount(2);
  $this->assertDatabaseCount('source_retrieval_runs',2);
 }

 public function test_completed_retrieval_is_reused_for_the_same_idempotency_key(): void {
  Http::fake(['https://example.test/*'=>Http::response('official content',200,['Content-Type'=>'text/html'])]);
  $source=Source::create(['source_type'=>'web','name'=>'Idempotent Official','url'=>'https://example.test/idempotent','status'=>'active']);
  $service=app(\App\Services\SourceRetrievalService::class);
  $first=$service->retrieve($source,'stable-retrieval-key');
  $second=$service->retrieve($source,'stable-retrieval-key');
  $this->assertSame($first->id,$second->id);
  $this->assertSame('completed',$second->status);
  Http::assertSentCount(1);
  $this->assertDatabaseCount('source_retrieval_runs',1);
 }


 public function test_idempotency_key_cannot_be_reused_for_a_different_source(): void {
  Http::fake(['https://example.test/*'=>Http::response('official content',200,['Content-Type'=>'text/html'])]);
  $firstSource=Source::create(['source_type'=>'web','name'=>'First Source','url'=>'https://example.test/first','status'=>'active']);
  $secondSource=Source::create(['source_type'=>'web','name'=>'Second Source','url'=>'https://example.test/second','status'=>'active']);
  $service=app(\App\Services\SourceRetrievalService::class);
  $service->retrieve($firstSource,'shared-source-key');
  $this->expectException(\InvalidArgumentException::class);
  $service->retrieve($secondSource,'shared-source-key');
 }

 public function test_failed_retrieval_can_retry_using_the_same_idempotency_key(): void {
  Http::fakeSequence()
   ->push('temporary failure',503)
   ->push('official content',200,['Content-Type'=>'text/html']);
  $source=Source::create(['source_type'=>'web','name'=>'Retryable Official','url'=>'https://example.test/retryable','status'=>'active']);
  $service=app(\App\Services\SourceRetrievalService::class);
  $first=$service->retrieve($source,'retryable-retrieval-key');
  $second=$service->retrieve($source,'retryable-retrieval-key');
  $this->assertSame($first->id,$second->id);
  $this->assertSame('completed',$second->status);
  Http::assertSentCount(2);
  $this->assertDatabaseCount('source_retrieval_runs',1);
 }


 public function test_fresh_in_progress_retrieval_is_not_started_twice(): void {
  $source=Source::create(['source_type'=>'web','name'=>'In Progress Official','url'=>'https://example.test/in-progress','status'=>'active']);
  $run=\App\Models\SourceRetrievalRun::create([
   'source_id'=>$source->id,
   'idempotency_key'=>'in-progress-retrieval-key',
   'status'=>'started',
   'started_at'=>now(),
  ]);
  $reused=app(\App\Services\SourceRetrievalService::class)->retrieve($source,'in-progress-retrieval-key');
  $this->assertSame($run->id,$reused->id);
  $this->assertSame('started',$reused->status);
  Http::assertNothingSent();
  $this->assertDatabaseCount('source_retrieval_runs',1);
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

    public function test_snapshot_persistence_exception_is_not_misclassified_as_retrieval_failure(): void
    {
        Http::fake([
            'https://example.test/*' => Http::response('official content', 200, ['Content-Type' => 'text/html']),
        ]);

        $source = Source::create([
            'source_type' => 'web',
            'name' => 'Snapshot Storage Failure',
            'url' => 'https://example.test/snapshot-storage-failure',
            'status' => 'active',
        ]);

        $snapshots = \Mockery::mock(\App\Services\SourceSnapshotService::class);
        $snapshots->shouldReceive('capture')
            ->once()
            ->andThrow(new \RuntimeException('database unavailable'));

        $this->app->instance(\App\Services\SourceSnapshotService::class, $snapshots);

        try {
            app(\App\Services\SourceRetrievalService::class)->retrieve($source, 'snapshot-storage-failure-key');
            $this->fail('Snapshot persistence errors must propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('database unavailable', $exception->getMessage());
        }

        $this->assertDatabaseHas('source_retrieval_runs', [
            'source_id' => $source->id,
            'idempotency_key' => 'snapshot-storage-failure-key',
            'status' => 'started',
            'error_message' => null,
        ]);

        $this->assertDatabaseCount('source_snapshots', 0);
    }


    public function test_retrieval_capture_timestamp_is_the_response_observation_time(): void
    {
        \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::parse('2026-11-04 09:00:00'));
        Http::fake([
            'https://example.test/*' => function () {
                \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::parse('2026-11-04 09:05:00'));

                return Http::response('delayed official content', 200, ['Content-Type' => 'text/html']);
            },
        ]);
        $source = Source::create(['source_type' => 'web', 'name' => 'Delayed Completion Source', 'url' => 'https://example.test/delayed', 'status' => 'active']);

        try {
            $run = app(\App\Services\SourceRetrievalService::class)->retrieve($source, 'delayed-completion-key');
            $snapshot = \App\Models\SourceSnapshot::query()->findOrFail($run->snapshot_id);

            $this->assertSame('2026-11-04 09:05:00', $snapshot->captured_at->format('Y-m-d H:i:s'));
            $this->assertSame($snapshot->captured_at->toISOString(), $run->completed_at->toISOString());
            $this->assertTrue($run->completed_at->greaterThan($run->started_at));
        } finally {
            \Illuminate\Support\Carbon::setTestNow();
        }
    }

    public function test_pipeline_retry_after_snapshot_persistence_and_comparison_failure_reuses_capture(): void
    {
        Http::fake(['https://example.test/*' => Http::response('persisted before comparison', 200, ['Content-Type' => 'text/html'])]);
        $source = Source::create(['source_type' => 'web', 'name' => 'Comparison Retry Source', 'url' => 'https://example.test/comparison-retry', 'status' => 'active']);
        $comparison = \Mockery::mock(\App\Services\SourceChangeObservationService::class);
        $comparison->shouldReceive('compare')->once()->andThrow(new \RuntimeException('injected comparison interruption'));
        $this->app->instance(\App\Services\SourceChangeObservationService::class, $comparison);

        try {
            app(\App\Services\SourceMonitoringPipeline::class)->run($source, 'comparison-retry-key');
            $this->fail('Injected comparison interruption should propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('injected comparison interruption', $exception->getMessage());
        }

        $run = \App\Models\SourceRetrievalRun::query()->where('idempotency_key', 'comparison-retry-key')->firstOrFail();
        $this->assertSame('completed', $run->status);
        $this->assertNotNull($run->snapshot_id);
        $this->assertDatabaseCount('source_snapshots', 1);

        $this->app->instance(\App\Services\SourceChangeObservationService::class, new \App\Services\SourceChangeObservationService());
        $retried = app(\App\Services\SourceMonitoringPipeline::class)->run($source, 'comparison-retry-key');

        $this->assertSame($run->id, $retried['run']->id);
        $this->assertSame($run->snapshot_id, $retried['observation']->current_snapshot_id);
        $this->assertSame('baseline', $retried['observation']->change_state);
        Http::assertSentCount(1);
        $this->assertDatabaseCount('source_snapshots', 1);
        $this->assertDatabaseCount('source_change_observations', 1);
    }


    public function test_snapshot_persisted_before_run_finalization_failure_is_reused_on_retry(): void
    {
        \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::parse('2026-11-04 10:00:00'));
        Http::fakeSequence()
            ->push('same official payload', 200, ['Content-Type' => 'text/html'])
            ->push('same official payload', 200, ['Content-Type' => 'text/html']);

        $source = Source::create([
            'source_type' => 'web',
            'name' => 'Run Finalization Failure Source',
            'url' => 'https://example.test/finalization-failure',
            'status' => 'active',
        ]);

        $finalizer = \Mockery::mock(\App\Services\SourceRetrievalRunFinalizer::class);
        $finalizer->shouldReceive('complete')
            ->once()
            ->andThrow(new \RuntimeException('injected run finalization failure'));
        $this->app->instance(\App\Services\SourceRetrievalRunFinalizer::class, $finalizer);

        try {
            app(\App\Services\SourceRetrievalService::class)->retrieve($source, 'finalization-failure-key');
            $this->fail('The injected finalization failure must propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('injected run finalization failure', $exception->getMessage());
        }

        $run = \App\Models\SourceRetrievalRun::query()
            ->where('idempotency_key', 'finalization-failure-key')
            ->firstOrFail();
        $this->assertSame('started', $run->status);
        $this->assertNull($run->snapshot_id);
        $this->assertDatabaseCount('source_snapshots', 1);
        $snapshot = \App\Models\SourceSnapshot::query()->firstOrFail();
        $originalSnapshotId = $snapshot->id;
        $originalCapturedAt = $snapshot->captured_at->toISOString();

        \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::parse('2026-11-04 10:16:00'));
        $this->app->instance(
            \App\Services\SourceRetrievalRunFinalizer::class,
            new \App\Services\SourceRetrievalRunFinalizer()
        );

        try {
            $retried = app(\App\Services\SourceRetrievalService::class)->retrieve($source, 'finalization-failure-key');

            $this->assertSame($run->id, $retried->id);
            $this->assertSame('completed', $retried->status);
            $this->assertSame($originalSnapshotId, $retried->snapshot_id);
            $this->assertSame($originalCapturedAt, $retried->completed_at->toISOString());
            $this->assertDatabaseCount('source_retrieval_runs', 1);
            $this->assertDatabaseCount('source_snapshots', 1);
            Http::assertSentCount(2);
        } finally {
            \Illuminate\Support\Carbon::setTestNow();
        }
    }


}

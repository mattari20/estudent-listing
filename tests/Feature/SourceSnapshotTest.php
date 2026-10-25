<?php
namespace Tests\Feature;
use App\Models\Source;
use App\Services\SourceChangeObservationService;
use App\Services\SourceSnapshotService;
use App\Services\SourceMaterialityClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SourceSnapshotTest extends TestCase {
 use RefreshDatabase;
 public function test_identical_snapshots_are_observed_as_unchanged(): void {
  $source=Source::create(['source_type'=>'web','name'=>'Snapshot Source','status'=>'active']);
  $service=app(SourceSnapshotService::class); $first=$service->capture($source,'same content','https://example.test');
  $second=$service->capture($source,'same content','https://example.test');
  $observation=app(SourceChangeObservationService::class)->compare($second);
  $this->assertSame('unchanged',$observation->change_state);
  $this->assertSame($first->content_hash,$second->content_hash);
 }
 public function test_changed_snapshot_requires_separate_materiality_classification(): void {
  $source=Source::create(['source_type'=>'web','name'=>'Change Source','status'=>'active']);
  $service=app(SourceSnapshotService::class); $service->capture($source,'old','https://example.test'); $second=$service->capture($source,'new','https://example.test');
  $observation=app(SourceChangeObservationService::class)->compare($second);
  $this->assertSame('changed',$observation->change_state); $this->assertSame('unknown',$observation->materiality);
  app(SourceMaterialityClassifier::class)->classify($observation,'high','Official status content requires operator review.');
  $this->assertSame('high',$observation->refresh()->materiality);
 }

    public function test_changed_source_content_creates_a_new_snapshot_without_overwriting_old_content(): void
 {
  $source=Source::create(['source_type'=>'web','name'=>'History Source','status'=>'active']);
  $service=app(SourceSnapshotService::class);
  $first=$service->capture($source,'first official content','https://example.test/history');
  $second=$service->capture($source,'updated official content','https://example.test/history');
  $this->assertNotSame($first->id,$second->id);
  $this->assertSame('first official content',$first->refresh()->content);
  $this->assertSame('updated official content',$second->content);
 }

    public function test_capture_of_changed_content_preserves_previous_snapshot(): void
 {
  $source = Source::create(['source_type' => 'web', 'name' => 'Snapshot History', 'status' => 'active']);
  $service = app(SourceSnapshotService::class);
  $first = $service->capture($source, 'version one', 'https://example.test/history');
  $second = $service->capture($source, 'version two', 'https://example.test/history');
  $this->assertNotSame($first->id, $second->id);
  $this->assertSame('version one', $first->fresh()->content);
  $this->assertSame('version two', $second->content);
 }

    public function test_missing_snapshot_hash_is_uncomparable_not_a_detected_change(): void
    {
        $source = Source::create(['source_type' => 'web', 'name' => 'Incomplete Snapshot Source', 'status' => 'active']);
        $first = \App\Models\SourceSnapshot::create([
            'source_id' => $source->id,
            'reference' => 'https://example.test/incomplete',
            'content_hash' => null,
            'content_length' => 12,
            'content_type' => 'text/html',
            'content' => 'old content',
            'captured_at' => now()->subMinute(),
            'status' => 'captured',
        ]);
        $second = \App\Models\SourceSnapshot::create([
            'source_id' => $source->id,
            'reference' => 'https://example.test/incomplete',
            'content_hash' => null,
            'content_length' => 12,
            'content_type' => 'text/html',
            'content' => 'new content',
            'captured_at' => now(),
            'status' => 'captured',
        ]);

        $observation = app(SourceChangeObservationService::class)->compare($second);

        $this->assertSame('uncomparable', $observation->change_state);
        $this->assertSame('unknown', $observation->materiality);
        $this->assertStringContainsString('inconclusive', $observation->summary);
    }
}

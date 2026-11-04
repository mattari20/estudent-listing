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

    public function test_comparing_the_same_snapshot_twice_reuses_one_observation(): void
    {
        $source = Source::create(['source_type' => 'web', 'name' => 'Idempotent Observation Source', 'status' => 'active']);
        $snapshot = app(SourceSnapshotService::class)->capture($source, 'stable official content', 'https://example.test/stable');
        $service = app(SourceChangeObservationService::class);

        $first = $service->compare($snapshot);
        $second = $service->compare($snapshot);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('source_change_observations', 1);
    }


    public function test_same_capture_key_reuses_snapshot_without_creating_duplicate_history(): void
    {
        $source = Source::create(['source_type' => 'web', 'name' => 'Idempotent Capture Source', 'status' => 'active']);
        $service = app(SourceSnapshotService::class);

        $first = $service->capture($source, 'stable payload', 'https://example.test/stable', 'text/html', [], 'retrieval-capture-key');
        $retry = $service->capture($source, 'stable payload', 'https://example.test/stable', 'text/html', [], 'retrieval-capture-key');

        $this->assertSame($first->id, $retry->id);
        $this->assertDatabaseCount('source_snapshots', 1);
    }

    public function test_distinct_capture_keys_preserve_distinct_identical_content_snapshots(): void
    {
        $source = Source::create(['source_type' => 'web', 'name' => 'Distinct Capture Source', 'status' => 'active']);
        $service = app(SourceSnapshotService::class);

        $first = $service->capture($source, 'same payload', 'https://example.test/stable', 'text/html', [], 'retrieval-capture-one');
        $second = $service->capture($source, 'same payload', 'https://example.test/stable', 'text/html', [], 'retrieval-capture-two');

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame($first->content_hash, $second->content_hash);
        $this->assertDatabaseCount('source_snapshots', 2);
    }

    public function test_capture_key_cannot_be_reused_for_different_content_or_source(): void
    {
        $source = Source::create(['source_type' => 'web', 'name' => 'Capture Key Source', 'status' => 'active']);
        $other = Source::create(['source_type' => 'web', 'name' => 'Other Capture Key Source', 'status' => 'active']);
        $service = app(SourceSnapshotService::class);
        $service->capture($source, 'original payload', 'https://example.test/original', 'text/html', [], 'bound-capture-key');

        $this->expectException(\LogicException::class);
        $service->capture($other, 'different payload', 'https://example.test/other', 'text/html', [], 'bound-capture-key');
    }

    public function test_observation_database_identity_is_unique_per_current_snapshot(): void
    {
        $source = Source::create(['source_type' => 'web', 'name' => 'Unique Observation Source', 'status' => 'active']);
        $snapshot = app(SourceSnapshotService::class)->capture($source, 'one payload', 'https://example.test/one');
        $service = app(SourceChangeObservationService::class);

        $first = $service->compare($snapshot);
        $second = $service->compare($snapshot);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('source_change_observations', 1);
    }


    public function test_database_rejects_duplicate_observations_for_one_snapshot(): void
    {
        $source = Source::create(['source_type' => 'web', 'name' => 'Observation Constraint Source', 'status' => 'active']);
        $snapshot = app(SourceSnapshotService::class)->capture($source, 'one payload', 'https://example.test/one');
        $attributes = [
            'source_id' => $source->id,
            'current_snapshot_id' => $snapshot->id,
            'change_state' => 'baseline',
            'materiality' => 'unknown',
            'status' => 'detected',
            'summary' => 'Initial snapshot.',
            'observed_at' => now(),
        ];

        \App\Models\SourceChangeObservation::create($attributes);
        $this->expectException(\Illuminate\Database\QueryException::class);
        \App\Models\SourceChangeObservation::create($attributes);
    }

    public function test_changed_observation_retains_before_and_after_snapshot_evidence(): void
    {
        $source = Source::create(['source_type' => 'official', 'name' => 'Evidence History Source', 'status' => 'active']);
        $service = app(SourceSnapshotService::class);
        $first = $service->capture($source, 'official page version one', 'https://university.example.edu/programs', 'text/html');
        $second = $service->capture($source, 'official page version two', 'https://university.example.edu/programs', 'text/html');

        $observation = app(SourceChangeObservationService::class)->compare($second);

        $this->assertSame('changed', $observation->change_state);
        $this->assertSame($first->id, $observation->details['previous']['snapshot_id']);
        $this->assertSame($second->id, $observation->details['current']['snapshot_id']);
        $this->assertSame($first->content_hash, $observation->details['previous']['content_hash']);
        $this->assertSame($second->content_hash, $observation->details['current']['content_hash']);
        $this->assertSame('https://university.example.edu/programs', $observation->details['current']['reference']);
    }


    public function test_normal_chronological_snapshots_select_the_immediate_predecessor(): void
    {
        $source = Source::create(['source_type' => 'web', 'name' => 'Chronological Source', 'status' => 'active']);
        $service = app(SourceSnapshotService::class);
        $a = $service->capture($source, 'A', 'https://example.test/chronology', 'text/plain', [], null, \Illuminate\Support\Carbon::parse('2026-11-01 10:00:00'));
        $b = $service->capture($source, 'B', 'https://example.test/chronology', 'text/plain', [], null, \Illuminate\Support\Carbon::parse('2026-11-02 10:00:00'));
        $c = $service->capture($source, 'C', 'https://example.test/chronology', 'text/plain', [], null, \Illuminate\Support\Carbon::parse('2026-11-03 10:00:00'));
        $compare = app(SourceChangeObservationService::class);

        $observationA = $compare->compare($a);
        $observationB = $compare->compare($b);
        $observationC = $compare->compare($c);

        $this->assertNull($observationA->previous_snapshot_id);
        $this->assertSame($a->id, $observationB->previous_snapshot_id);
        $this->assertSame($b->id, $observationC->previous_snapshot_id);
        $this->assertSame('changed', $observationB->change_state);
        $this->assertSame('changed', $observationC->change_state);
    }

    public function test_late_middle_snapshot_queues_explicit_reconciliation_without_rewriting_history(): void
    {
        $source = Source::create(['source_type' => 'web', 'name' => 'Late Snapshot Source', 'status' => 'active']);
        $service = app(SourceSnapshotService::class);
        $a = $service->capture($source, 'A', 'https://example.test/late', 'text/plain', [], null, \Illuminate\Support\Carbon::parse('2026-11-01 10:00:00'));
        $c = $service->capture($source, 'C', 'https://example.test/late', 'text/plain', [], null, \Illuminate\Support\Carbon::parse('2026-11-03 10:00:00'));
        $compare = app(SourceChangeObservationService::class);
        $compare->compare($a);
        $observationC = $compare->compare($c);
        $this->assertSame($a->id, $observationC->previous_snapshot_id);

        $b = $service->capture($source, 'B', 'https://example.test/late', 'text/plain', [], null, \Illuminate\Support\Carbon::parse('2026-11-02 10:00:00'));
        $observationB = $compare->compare($b);
        $observationC->refresh();

        $this->assertSame($a->id, $observationB->previous_snapshot_id);
        $this->assertSame($a->id, $observationC->previous_snapshot_id, 'Historical comparison is not silently rewritten.');
        $request = \App\Models\SourceSnapshotReconciliationRequest::query()
            ->where('current_snapshot_id', $c->id)
            ->where('triggering_snapshot_id', $b->id)
            ->firstOrFail();
        $this->assertSame($b->id, $request->expected_previous_snapshot_id);
        $this->assertSame($a->id, $request->recorded_previous_snapshot_id);
        $this->assertSame('pending_review', $request->status);

        $compare->compare($b);
        $this->assertDatabaseCount('source_snapshot_reconciliation_requests', 1);
    }

    public function test_equal_capture_timestamps_use_snapshot_id_as_stable_tie_breaker(): void
    {
        $source = Source::create(['source_type' => 'web', 'name' => 'Equal Timestamp Source', 'status' => 'active']);
        $service = app(SourceSnapshotService::class);
        $timestamp = \Illuminate\Support\Carbon::parse('2026-11-02 10:00:00');
        $first = $service->capture($source, 'first', 'https://example.test/equal', 'text/plain', [], null, $timestamp);
        $second = $service->capture($source, 'second', 'https://example.test/equal', 'text/plain', [], null, $timestamp);

        $observation = app(SourceChangeObservationService::class)->compare($second);

        $this->assertSame($first->id, $observation->previous_snapshot_id);
        $this->assertSame($first->captured_at->toISOString(), $second->captured_at->toISOString());
    }

    public function test_snapshot_capture_replay_preserves_original_timestamp_and_observation_identity(): void
    {
        $source = Source::create(['source_type' => 'web', 'name' => 'Capture Replay Source', 'status' => 'active']);
        $service = app(SourceSnapshotService::class);
        $timestamp = \Illuminate\Support\Carbon::parse('2026-11-02 10:00:00');
        $first = $service->capture($source, 'stable', 'https://example.test/replay', 'text/plain', [], 'chronology-replay-key', $timestamp);
        $replay = $service->capture($source, 'stable', 'https://example.test/replay', 'text/plain', [], 'chronology-replay-key', now());
        $compare = app(SourceChangeObservationService::class);

        $firstObservation = $compare->compare($first);
        $replayObservation = $compare->compare($replay);

        $this->assertSame($first->id, $replay->id);
        $this->assertSame($timestamp->toISOString(), $replay->captured_at->toISOString());
        $this->assertSame($firstObservation->id, $replayObservation->id);
        $this->assertDatabaseCount('source_snapshots', 1);
        $this->assertDatabaseCount('source_change_observations', 1);
    }

    public function test_late_snapshot_does_not_change_snapshot_content_or_source_ownership(): void
    {
        $source = Source::create(['source_type' => 'web', 'name' => 'Ownership Source', 'status' => 'active']);
        $other = Source::create(['source_type' => 'web', 'name' => 'Other Ownership Source', 'status' => 'active']);
        $service = app(SourceSnapshotService::class);
        $later = $service->capture($source, 'later payload', 'https://example.test/owned', 'text/plain', [], null, \Illuminate\Support\Carbon::parse('2026-11-03 10:00:00'));
        $compare = app(SourceChangeObservationService::class);
        $laterObservation = $compare->compare($later);
        $earlier = $service->capture($source, 'earlier payload', 'https://example.test/owned', 'text/plain', [], null, \Illuminate\Support\Carbon::parse('2026-11-02 10:00:00'));
        $compare->compare($earlier);

        $this->assertSame($source->id, $later->fresh()->source_id);
        $this->assertSame($source->id, $earlier->fresh()->source_id);
        $this->assertSame('later payload', $later->fresh()->content);
        $this->assertSame('earlier payload', $earlier->fresh()->content);
        $this->assertSame($later->id, $laterObservation->fresh()->current_snapshot_id);
        $this->assertNotSame($other->id, $later->fresh()->source_id);
    }

}

<?php

namespace Tests\Feature;

use App\Models\IdentityMatchCandidate;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportBatch;
use App\Models\ImportConflict;
use App\Models\ImportConflictEvent;
use App\Models\ImportPromotionEvent;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Source;
use App\Services\IdentityResolutionDecisionService;
use App\Services\ImportConflictDetectionService;
use App\Services\ImportConflictLifecycleService;
use App\Services\InstituteIdentityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class IdentityConflictResolutionReplayTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(bool $withCanonicalMatch = true): array
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Identity replay source',
            'url' => 'https://example.test/identity-replay',
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => 'identity-replay-'.bin2hex(random_bytes(6)),
            'entity_type' => 'institution',
            'source_type' => 'official',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 2,
            'valid_rows' => 2,
            'candidate_rows' => 2,
            'received_at' => now(),
        ]);
        $canonical = null;
        if ($withCanonicalMatch) {
            $canonical = Institution::create([
                'name' => 'Identity Replay University',
                'slug' => 'identity-replay-university',
                'status' => 'active',
            ]);
        }
        $row = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'unresolved',
            'payload' => ['name' => 'Identity Replay University', 'slug' => 'identity-replay-university'],
            'normalized_payload' => ['name' => 'Identity Replay University', 'slug' => 'identity-replay-university'],
        ]);
        $other = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 2,
            'status' => 'candidate',
            'match_state' => 'unresolved',
            'payload' => ['name' => 'Identity Replay University Branch', 'slug' => 'identity-replay-university'],
            'normalized_payload' => ['name' => 'Identity Replay University Branch', 'slug' => 'identity-replay-university'],
        ]);

        return [$source, $batch, $row, $other, $canonical];
    }

    public function test_identity_match_candidate_replay_keeps_one_durable_candidate(): void
    {
        [$source, $batch, $row, $other, $canonical] = $this->fixture();
        $resolver = app(InstituteIdentityResolver::class);

        $this->assertSame(1, $resolver->resolve($row));
        $this->assertSame(1, $resolver->resolve($row->fresh()));

        $this->assertDatabaseCount('identity_match_candidates', 1);
        $this->assertDatabaseHas('identity_match_candidates', [
            'import_row_id' => $row->id,
            'institution_id' => $canonical->id,
            'status' => 'candidate',
            'identity_key' => hash('sha256', $row->id.'|'.$canonical->id),
        ]);
        $this->assertSame('conflict', $row->fresh()->match_state);
    }

    public function test_conflict_detection_replay_creates_one_open_conflict_and_one_open_event(): void
    {
        [$source, $batch, $row] = $this->fixture();
        $detector = app(ImportConflictDetectionService::class);

        $detector->detect($row);
        $detector->detect($row->fresh());

        $this->assertDatabaseCount('import_conflicts', 1);
        $this->assertDatabaseCount('import_conflict_events', 1);
        $this->assertDatabaseHas('import_conflicts', [
            'import_row_id' => $row->id,
            'conflict_type' => 'duplicate_batch_identity',
            'status' => 'open',
            'open_identity_key' => hash('sha256', $row->id.'|duplicate_batch_identity'),
        ]);
        $this->assertDatabaseHas('import_conflict_events', [
            'import_row_id' => $row->id,
            'event_type' => 'opened',
        ]);
    }

    public function test_conflict_resolution_is_idempotent_and_preserves_resolution_history(): void
    {
        [$source, $batch, $row] = $this->fixture();
        app(ImportConflictDetectionService::class)->detect($row);
        $conflict = ImportConflict::query()->where('import_row_id', $row->id)->firstOrFail();
        $lifecycle = app(ImportConflictLifecycleService::class);

        $first = $lifecycle->resolve($conflict, 701, 'Reviewed against the official source.');
        $second = $lifecycle->resolve($conflict->fresh(), 701, 'Reviewed against the official source.');

        $this->assertSame('resolved', $first->status);
        $this->assertSame('resolved', $second->status);
        $this->assertNull($second->open_identity_key);
        $this->assertDatabaseCount('import_conflict_events', 2);
        $this->assertDatabaseHas('import_conflict_events', [
            'import_conflict_id' => $conflict->id,
            'event_type' => 'resolved',
            'actor_id' => 701,
            'reason' => 'Reviewed against the official source.',
        ]);
    }

    public function test_replayed_conflict_detection_after_resolution_creates_a_new_open_incident_without_erasing_history(): void
    {
        [$source, $batch, $row] = $this->fixture();
        $detector = app(ImportConflictDetectionService::class);
        $lifecycle = app(ImportConflictLifecycleService::class);
        $detector->detect($row);
        $first = ImportConflict::query()->where('import_row_id', $row->id)->firstOrFail();
        $lifecycle->resolve($first, 702, 'First conflict reviewed.');
        $detector->detect($row->fresh());

        $this->assertDatabaseCount('import_conflicts', 2);
        $this->assertDatabaseCount('import_conflict_events', 3);
        $this->assertDatabaseHas('import_conflicts', [
            'id' => $first->id,
            'status' => 'resolved',
            'open_identity_key' => null,
        ]);
        $this->assertDatabaseHas('import_conflicts', [
            'import_row_id' => $row->id,
            'status' => 'open',
            'open_identity_key' => hash('sha256', $row->id.'|duplicate_batch_identity'),
        ]);
    }

    public function test_identity_decision_replay_creates_one_decision_and_one_audit_event(): void
    {
        [$source, $batch, $row] = $this->fixture(false);
        $service = app(IdentityResolutionDecisionService::class);

        $first = $service->record($row, 'rejected', null, 'test', 703, 'No verified identity.', 'promotion_rejected');
        $second = $service->record($row->fresh(), 'rejected', null, 'test', 703, 'No verified identity.', 'promotion_rejected');

        $this->assertTrue($first['created']);
        $this->assertFalse($second['created']);
        $this->assertSame($first['decision']->id, $second['decision']->id);
        $this->assertDatabaseCount('identity_resolution_decisions', 1);
        $this->assertDatabaseCount('import_promotion_events', 1);
        $this->assertDatabaseHas('identity_resolution_decisions', [
            'idempotency_key' => hash('sha256', implode("\0", [
                $row->id, 'rejected', 'null', 'test', 703, 'No verified identity.',
            ])),
        ]);
    }

    public function test_distinct_identity_decision_reasons_remain_separate_history_entries(): void
    {
        [$source, $batch, $row] = $this->fixture(false);
        $service = app(IdentityResolutionDecisionService::class);

        $service->record($row, 'rejected', null, 'test', 704, 'First review reason.', 'promotion_rejected');
        $service->record($row, 'rejected', null, 'test', 704, 'Second review reason.', 'promotion_rejected');

        $this->assertDatabaseCount('identity_resolution_decisions', 2);
        $this->assertDatabaseCount('import_promotion_events', 2);
    }

    public function test_identity_decision_and_audit_roll_back_together_then_replay_successfully(): void
    {
        [$source, $batch, $row] = $this->fixture(false);
        $service = app(IdentityResolutionDecisionService::class);

        try {
            DB::transaction(function () use ($service, $row): void {
                $service->record($row, 'rejected', null, 'test', 705, 'Rollback fixture.', 'promotion_rejected');
                throw new RuntimeException('Injected outer transaction failure.');
            });
            $this->fail('The outer transaction should fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected outer transaction failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('identity_resolution_decisions', 0);
        $this->assertDatabaseCount('import_promotion_events', 0);

        $result = $service->record($row->fresh(), 'rejected', null, 'test', 705, 'Rollback fixture.', 'promotion_rejected');
        $this->assertTrue($result['created']);
        $this->assertDatabaseCount('identity_resolution_decisions', 1);
        $this->assertDatabaseCount('import_promotion_events', 1);
    }

    public function test_conflict_open_and_open_event_roll_back_together_then_retry(): void
    {
        [$source, $batch, $row] = $this->fixture(false);
        $lifecycle = app(ImportConflictLifecycleService::class);

        try {
            DB::transaction(function () use ($lifecycle, $row): void {
                $lifecycle->open($row, 'test_conflict', 'high', 'Injected conflict.', null, null, []);
                throw new RuntimeException('Injected conflict transaction failure.');
            });
            $this->fail('The outer transaction should fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected conflict transaction failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('import_conflicts', 0);
        $this->assertDatabaseCount('import_conflict_events', 0);

        $this->assertSame(1, $lifecycle->open($row->fresh(), 'test_conflict', 'high', 'Injected conflict.', null, null, []));
        $this->assertDatabaseCount('import_conflicts', 1);
        $this->assertDatabaseCount('import_conflict_events', 1);
    }

    public function test_conflict_resolution_cannot_be_overwritten_by_a_different_replay(): void
    {
        [$source, $batch, $row] = $this->fixture(false);
        $lifecycle = app(ImportConflictLifecycleService::class);
        $lifecycle->open($row, 'manual_conflict', 'high', 'Manual conflict.', null, null, []);
        $conflict = ImportConflict::query()->where('import_row_id', $row->id)->firstOrFail();
        $lifecycle->resolve($conflict, 706, 'Original resolution.');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('original resolution history cannot be overwritten');
        $lifecycle->resolve($conflict->fresh(), 707, 'Different resolution.');
    }

    public function test_open_conflict_blocks_promotion_until_explicit_resolution_remains_intact(): void
    {
        [$source, $batch, $row] = $this->fixture();
        app(ImportConflictDetectionService::class)->detect($row);
        $conflict = ImportConflict::query()->where('import_row_id', $row->id)->firstOrFail();

        $this->assertSame('open', $conflict->status);
        $this->assertSame('conflict', $row->fresh()->match_state);
        $this->assertSame(1, ImportConflictEvent::where('import_conflict_id', $conflict->id)->where('event_type', 'opened')->count());
    }
}

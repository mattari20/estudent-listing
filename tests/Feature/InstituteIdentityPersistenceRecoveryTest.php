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
use App\Services\ImportConflictLifecycleService;
use App\Services\IdentityResolutionDecisionService;
use App\Services\ImportPromotionAuditService;
use App\Services\InstituteIdentityResolver;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class InstituteIdentityPersistenceRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_match_and_open_conflict_replay_remain_unique_and_conflicted(): void
    {
        [$row, $batch] = $this->identityRows();

        $resolver = app(InstituteIdentityResolver::class);
        $resolver->resolve($row);
        $resolver->resolve($row);

        $this->assertSame(1, IdentityMatchCandidate::query()->where('import_row_id', $row->id)->count());
        $this->assertSame(1, ImportConflict::query()->where('import_row_id', $row->id)->count());
        $this->assertSame(1, ImportConflictEvent::query()->where('event_type', 'detected')->count());
        $this->assertSame('conflict', $row->fresh()->match_state);
        $this->assertSame(2, $batch->rows()->count());
        $this->assertNotEmpty(IdentityMatchCandidate::query()->where('import_row_id', $row->id)->value('identity_key'));
        $this->assertNotEmpty(ImportConflict::query()->where('import_row_id', $row->id)->value('identity_key'));
    }

    public function test_conflict_resolution_is_append_only_and_same_request_replay_is_idempotent(): void
    {
        [$row] = $this->identityRows();
        $conflict = $this->openDuplicateConflict($row);

        $lifecycle = app(ImportConflictLifecycleService::class);
        $first = $lifecycle->resolve($row, $conflict->id, 701, 'Reviewed source identity and retained both historical rows.');
        $second = $lifecycle->resolve($row, $conflict->id, 701, 'Reviewed source identity and retained both historical rows.');

        $this->assertSame($first->id, $second->id);
        $this->assertSame('resolved', $second->status);
        $this->assertSame('new_candidate', $row->fresh()->match_state);
        $this->assertSame(1, ImportConflictEvent::query()->where('import_conflict_id', $conflict->id)->where('event_type', 'resolved')->count());
        $this->assertSame(2, ImportConflictEvent::query()->where('import_conflict_id', $conflict->id)->count());

        try {
            $lifecycle->resolve($row, $conflict->id, 702, 'Attempted to overwrite prior resolution.');
            $this->fail('A different replay must not overwrite a resolved conflict.');
        } catch (RuntimeException $exception) {
            $this->assertSame('This conflict has already been resolved; its resolution history is immutable.', $exception->getMessage());
        }

        $this->assertSame('Reviewed source identity and retained both historical rows.', $conflict->fresh()->resolution_reason);
        $this->assertSame(2, ImportConflictEvent::query()->where('import_conflict_id', $conflict->id)->count());
    }

    public function test_identity_decision_replay_is_idempotent_but_distinct_decisions_remain_history(): void
    {
        [$row, , $institution] = $this->identityRows();
        $service = app(IdentityResolutionDecisionService::class);

        $first = $service->record($row, 704, 'approved_match', $institution->id, 'Reviewed official identity evidence.');
        $replay = $service->record($row, 704, 'approved_match', $institution->id, 'Reviewed official identity evidence.');

        $this->assertSame($first->id, $replay->id);
        $this->assertSame(1, IdentityResolutionDecision::query()->where('import_row_id', $row->id)->count());
        $this->assertSame(1, ImportPromotionEvent::query()->where('import_row_id', $row->id)->where('decision_type', 'identity_resolution')->count());
        $this->assertNotEmpty($first->decision_identity_key);

        $changedDecision = $service->record($row, 704, 'approved_match', $institution->id, 'A later review added further official evidence.');
        $this->assertNotSame($first->id, $changedDecision->id);
        $this->assertSame(2, IdentityResolutionDecision::query()->where('import_row_id', $row->id)->count());
        $this->assertSame(2, ImportPromotionEvent::query()->where('import_row_id', $row->id)->where('decision_type', 'identity_resolution')->count());
    }

    public function test_identical_replay_reuses_a_legacy_decision_without_duplicate_history(): void
    {
        [$row, , $institution] = $this->identityRows();
        $legacy = IdentityResolutionDecision::create([
            'import_row_id' => $row->id,
            'institution_id' => $institution->id,
            'decision' => 'approved_match',
            'actor_type' => 'user',
            'actor_id' => 705,
            'reason' => 'Legacy operator decision.',
            'decided_at' => now(),
        ]);
        app(ImportPromotionAuditService::class)->record(
            $row,
            'promotion_approved',
            705,
            'Legacy operator decision.',
            ['decision' => 'approved_match'],
            'identity_resolution',
            $legacy->id
        );

        $replayed = app(IdentityResolutionDecisionService::class)->record(
            $row,
            705,
            'approved_match',
            $institution->id,
            'Legacy operator decision.'
        );

        $this->assertSame($legacy->id, $replayed->id);
        $this->assertNotEmpty($replayed->decision_identity_key);
        $this->assertSame(1, IdentityResolutionDecision::query()->where('import_row_id', $row->id)->count());
        $this->assertSame(1, ImportPromotionEvent::query()->where('import_row_id', $row->id)->where('decision_type', 'identity_resolution')->count());
    }

    public function test_resolution_event_failure_rolls_back_conflict_and_row_state(): void
    {
        [$row] = $this->identityRows();
        $conflict = $this->openDuplicateConflict($row);

        DB::connection()->beforeExecuting(function (string $query): void {
            $sql = strtolower(ltrim($query));
            if (str_starts_with($sql, 'insert') && str_contains($sql, 'import_conflict_events')) {
                throw new RuntimeException('injected conflict event failure');
            }
        });

        try {
            app(ImportConflictLifecycleService::class)->resolve($row, $conflict->id, 703, 'Resolution attempt with injected event failure.');
            $this->fail('The event failure must abort the conflict resolution transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('injected conflict event failure', $exception->getMessage());
        }

        $this->assertSame('open', $conflict->fresh()->status);
        $this->assertSame('conflict', $row->fresh()->match_state);
        $this->assertSame(1, ImportConflictEvent::query()->where('import_conflict_id', $conflict->id)->count());
    }

    public function test_conflict_identity_key_is_database_unique(): void
    {
        [$row] = $this->identityRows();
        $conflict = $this->openDuplicateConflict($row);

        $this->expectException(QueryException::class);
        ImportConflict::create([
            'import_row_id' => $row->id,
            'import_batch_id' => $row->import_batch_id,
            'conflict_type' => 'duplicate_batch_identity',
            'identity_key' => $conflict->identity_key,
            'severity' => 'high',
            'status' => 'open',
            'description' => 'Duplicate unique identity key fixture.',
        ]);
    }

    private function identityRows(): array
    {
        $institution = Institution::create([
            'name' => 'Phase 1C-19 Canonical Institute',
            'official_name' => 'Phase 1C-19 Canonical Institute',
            'slug' => 'phase-1c-19-canonical-institute',
            'status' => 'active',
        ]);

        $batch = ImportBatch::create([
            'batch_key' => 'phase-1c-19-'.bin2hex(random_bytes(5)),
            'entity_type' => 'institution',
            'source_type' => 'manual',
            'status' => 'validated',
            'total_rows' => 2,
            'valid_rows' => 2,
            'candidate_rows' => 2,
            'received_at' => now(),
            'completed_at' => now(),
        ]);

        $row = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'new_candidate',
            'payload' => ['name' => $institution->name, 'slug' => $institution->slug],
            'normalized_payload' => ['name' => $institution->name, 'official_name' => $institution->official_name, 'slug' => $institution->slug],
        ]);

        ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 2,
            'status' => 'candidate',
            'match_state' => 'new_candidate',
            'payload' => ['name' => 'Conflicting row name', 'slug' => $institution->slug],
            'normalized_payload' => ['name' => 'Conflicting row name', 'slug' => $institution->slug],
        ]);

        return [$row, $batch, $institution];
    }

    private function openDuplicateConflict(ImportRow $row): ImportConflict
    {
        $otherRowId = ImportRow::query()
            ->where('import_batch_id', $row->import_batch_id)
            ->where('id', '!=', $row->id)
            ->value('id');

        return app(ImportConflictLifecycleService::class)->open(
            $row,
            'duplicate_batch_identity',
            'high',
            'Another row in the same batch has the same normalized slug.',
            null,
            (int) $otherRowId,
            ['slug' => 'phase-1c-19-canonical-institute']
        );
    }
}

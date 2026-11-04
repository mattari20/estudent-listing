<?php

namespace Tests\Feature;

use App\Models\Assertion;
use App\Models\Evidence;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportBatch;
use App\Models\ImportConflict;
use App\Models\ImportPromotionDecision;
use App\Models\ImportPromotionEvent;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Source;
use App\Services\IdentityResolutionDecisionService;
use App\Services\ImportPromotionAuditService;
use App\Services\ImportPromotionReversalService;
use App\Services\OperatorTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class OperatorTransitionAtomicityTest extends TestCase
{
    use RefreshDatabase;

    public function test_identical_institution_rejection_replay_is_idempotent_and_competing_rejection_is_rejected(): void
    {
        [$row] = $this->row('institution');
        $service = app(OperatorTransitionService::class);

        $service->rejectRow($row, 801, 'Official source does not support this institution record.');
        $service->rejectRow($row, 801, 'Official source does not support this institution record.');

        $this->assertSame('rejected', $row->fresh()->status);
        $this->assertSame(1, IdentityResolutionDecision::query()->where('import_row_id', $row->id)->count());
        $this->assertSame(1, ImportPromotionEvent::query()->where('import_row_id', $row->id)->where('event_type', 'promotion_rejected')->count());

        $this->expectException(RuntimeException::class);
        $service->rejectRow($row, 802, 'A competing rejection reason.');
    }

    public function test_existing_identity_rejection_decision_gets_a_separate_atomic_row_transition_event(): void
    {
        [$row] = $this->row('institution');
        $reason = 'Identity decision and row rejection are distinct operator transitions.';
        app(IdentityResolutionDecisionService::class)->record($row, 814, 'rejected', null, $reason);

        app(OperatorTransitionService::class)->rejectRow($row, 814, $reason);

        $events = ImportPromotionEvent::query()->where('import_row_id', $row->id)->where('decision_type', 'identity_resolution')->get();
        $this->assertSame('rejected', $row->fresh()->status);
        $this->assertSame(2, $events->count());
        $this->assertTrue($events->contains(fn (ImportPromotionEvent $event): bool => ($event->metadata['operator_action'] ?? null) === 'reject_import_row'));
    }

    public function test_identity_decision_insert_failure_rolls_back_and_allows_retry(): void
    {
        [$row, , $institution] = $this->row('institution');
        $this->failQuery('identity_resolution_decisions', 'insert');

        try {
            app(IdentityResolutionDecisionService::class)->record($row, 803, 'approved_match', $institution->id, 'Reviewed official identity evidence.');
            $this->fail('The injected identity-decision insert failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('injected identity_resolution_decisions insert failure', $exception->getMessage());
        }

        $this->assertSame(0, IdentityResolutionDecision::query()->where('import_row_id', $row->id)->count());
        $this->assertSame(0, ImportPromotionEvent::query()->where('import_row_id', $row->id)->count());

        $decision = app(IdentityResolutionDecisionService::class)->record($row, 803, 'approved_match', $institution->id, 'Reviewed official identity evidence.');
        $this->assertNotNull($decision->id);
        $this->assertSame(1, ImportPromotionEvent::query()->where('decision_id', $decision->id)->count());
    }

    public function test_identity_decision_audit_failure_rolls_back_decision_and_allows_retry(): void
    {
        [$row, , $institution] = $this->row('institution');
        $this->failQuery('import_promotion_events', 'insert');

        try {
            app(IdentityResolutionDecisionService::class)->record($row, 804, 'approved_match', $institution->id, 'Reviewed official identity evidence.');
            $this->fail('The injected audit insert failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('injected import_promotion_events insert failure', $exception->getMessage());
        }

        $this->assertSame(0, IdentityResolutionDecision::query()->where('import_row_id', $row->id)->count());
        $decision = app(IdentityResolutionDecisionService::class)->record($row, 804, 'approved_match', $institution->id, 'Reviewed official identity evidence.');
        $this->assertSame(1, IdentityResolutionDecision::query()->where('import_row_id', $row->id)->count());
        $this->assertSame(1, ImportPromotionEvent::query()->where('decision_id', $decision->id)->count());
    }

    public function test_candidate_state_update_failure_rolls_back_rejection_decision_and_audit(): void
    {
        [$row] = $this->row('institution');
        $this->failQuery('import_rows', 'update');

        try {
            app(OperatorTransitionService::class)->rejectRow($row, 805, 'Reject after candidate-state failure injection.');
            $this->fail('The injected candidate update failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('injected import_rows update failure', $exception->getMessage());
        }

        $this->assertSame('candidate', $row->fresh()->status);
        $this->assertSame(0, IdentityResolutionDecision::query()->where('import_row_id', $row->id)->count());
        $this->assertSame(0, ImportPromotionEvent::query()->where('import_row_id', $row->id)->count());
    }

    public function test_identity_decision_state_guard_runs_under_transaction_and_rejects_non_candidate_rows(): void
    {
        [$row, , $institution] = $this->row('institution', 'promoted');

        try {
            app(IdentityResolutionDecisionService::class)->record($row, 806, 'approved_match', $institution->id, 'Late decision must be blocked.');
            $this->fail('A non-candidate row must not accept a new identity decision.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Identity decisions can only be recorded while the import row is a candidate.', $exception->getMessage());
        }

        $this->assertSame(0, IdentityResolutionDecision::query()->where('import_row_id', $row->id)->count());
    }

    public function test_non_institution_rejection_and_audit_are_atomic_and_replay_safe(): void
    {
        [$row] = $this->row('campus');
        $service = app(OperatorTransitionService::class);

        $service->rejectRow($row, 812, 'Campus import is not supported by its official source.');
        $service->rejectRow($row, 812, 'Campus import is not supported by its official source.');

        $this->assertSame('rejected', $row->fresh()->status);
        $this->assertSame(1, ImportPromotionDecision::query()->where('import_row_id', $row->id)->count());
        $this->assertSame(1, ImportPromotionEvent::query()->where('import_row_id', $row->id)->where('event_type', 'promotion_rejected')->count());
    }

    public function test_existing_enrichment_rejection_decision_gets_a_row_transition_audit_event(): void
    {
        [$row] = $this->row('campus');
        $reason = 'Enrichment rejection is now applied to the import row.';
        app(OperatorTransitionService::class)->recordPromotionDecision($row, 815, 'rejected', $reason, 'enrichment_decision');

        app(OperatorTransitionService::class)->rejectRow($row, 815, $reason);

        $events = ImportPromotionEvent::query()->where('import_row_id', $row->id)->where('decision_type', 'import_promotion')->get();
        $this->assertSame('rejected', $row->fresh()->status);
        $this->assertSame(2, $events->count());
        $this->assertTrue($events->contains(fn (ImportPromotionEvent $event): bool => ($event->metadata['operator_action'] ?? null) === 'reject_import_row'));
    }

    public function test_quick_approval_decision_and_audit_are_atomic_and_replay_safe(): void
    {
        [$row] = $this->row('campus');
        $service = app(OperatorTransitionService::class);

        $first = $service->recordPromotionDecision($row, 807, 'approved', 'Reviewed parent institution.', 'quick_approve');
        $row->update(['status' => 'promoted']);
        $replay = $service->recordPromotionDecision($row, 807, 'approved', 'Reviewed parent institution.', 'quick_approve');

        $this->assertSame($first->id, $replay->id);
        $this->assertSame(1, ImportPromotionDecision::query()->where('import_row_id', $row->id)->count());
        $this->assertSame(1, ImportPromotionEvent::query()->where('decision_id', $first->id)->count());
    }

    public function test_publication_state_and_audit_commit_together_and_replay_without_duplicate_event(): void
    {
        [$row, $institution] = $this->promotableInstitutionRow();
        $service = app(OperatorTransitionService::class);

        $first = $service->publishInstitution($row, 808);
        $second = $service->publishInstitution($row, 808);

        $this->assertSame($institution->id, $first->id);
        $this->assertSame($institution->id, $second->id);
        $this->assertSame('active', $institution->fresh()->status);
        $this->assertSame(1, ImportPromotionEvent::query()->where('import_row_id', $row->id)->where('event_type', 'institution_published')->count());
    }

    public function test_publication_requires_the_latest_identity_decision_to_be_approved(): void
    {
        [$row, $institution] = $this->promotableInstitutionRow();
        IdentityResolutionDecision::create([
            'import_row_id' => $row->id,
            'institution_id' => null,
            'decision' => 'rejected',
            'actor_type' => 'user',
            'actor_id' => 813,
            'reason' => 'A later identity review rejected publication.',
            'decided_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        try {
            app(OperatorTransitionService::class)->publishInstitution($row, 808);
        } finally {
            $this->assertSame('candidate', $institution->fresh()->status);
            $this->assertSame(0, ImportPromotionEvent::query()->where('import_row_id', $row->id)->where('event_type', 'institution_published')->count());
        }
    }

    public function test_publication_remains_blocked_while_an_identity_conflict_is_open(): void
    {
        [$row, $institution] = $this->promotableInstitutionRow();
        ImportConflict::create([
            'import_row_id' => $row->id,
            'import_batch_id' => $row->import_batch_id,
            'conflict_type' => 'phase_1c_21_publication_guard',
            'severity' => 'high',
            'status' => 'open',
            'description' => 'Unresolved identity conflict blocks publication.',
        ]);

        $this->expectException(RuntimeException::class);
        try {
            app(OperatorTransitionService::class)->publishInstitution($row, 808);
        } finally {
            $this->assertSame('candidate', $institution->fresh()->status);
            $this->assertSame(0, ImportPromotionEvent::query()->where('import_row_id', $row->id)->where('event_type', 'institution_published')->count());
        }
    }

    public function test_publication_state_update_failure_rolls_back_and_can_be_retried(): void
    {
        [$row, $institution] = $this->promotableInstitutionRow();
        $this->failQuery('institutions', 'update');

        try {
            app(OperatorTransitionService::class)->publishInstitution($row, 809);
            $this->fail('The injected publication state update failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('injected institutions update failure', $exception->getMessage());
        }

        $this->assertSame('candidate', $institution->fresh()->status);
        $this->assertSame(0, ImportPromotionEvent::query()->where('import_row_id', $row->id)->where('event_type', 'institution_published')->count());

        app(OperatorTransitionService::class)->publishInstitution($row, 809);
        $this->assertSame('active', $institution->fresh()->status);
        $this->assertSame(1, ImportPromotionEvent::query()->where('import_row_id', $row->id)->where('event_type', 'institution_published')->count());
    }

    public function test_publication_audit_failure_rolls_back_canonical_status_and_can_be_retried(): void
    {
        [$row, $institution] = $this->promotableInstitutionRow();
        $this->failQuery('import_promotion_events', 'insert');

        try {
            app(OperatorTransitionService::class)->publishInstitution($row, 810);
            $this->fail('The injected publication audit failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('injected import_promotion_events insert failure', $exception->getMessage());
        }

        $this->assertSame('candidate', $institution->fresh()->status);
        $this->assertSame(0, ImportPromotionEvent::query()->where('import_row_id', $row->id)->where('event_type', 'institution_published')->count());

        app(OperatorTransitionService::class)->publishInstitution($row, 810);
        $this->assertSame('active', $institution->fresh()->status);
        $this->assertSame(1, ImportPromotionEvent::query()->where('import_row_id', $row->id)->where('event_type', 'institution_published')->count());
    }

    public function test_canonical_status_update_failure_rolls_back_reversal_state_and_audit(): void
    {
        [$row, $institution] = $this->row('institution', 'promoted');
        $row->update(['canonical_subject_type' => Institution::class, 'canonical_subject_id' => $institution->id]);
        app(ImportPromotionAuditService::class)->record($row, 'promotion_succeeded', 811, 'Created canonical institution.', ['created_canonical' => true]);
        $this->failQuery('institutions', 'update');

        try {
            app(ImportPromotionReversalService::class)->reverse($row, 811, 'Reverse with canonical status failure injection.');
            $this->fail('The injected canonical status update failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('injected institutions update failure', $exception->getMessage());
        }

        $this->assertSame('candidate', $institution->fresh()->status);
        $this->assertSame('promoted', $row->fresh()->status);
        $this->assertSame(0, ImportPromotionEvent::query()->where('import_row_id', $row->id)->where('event_type', 'promotion_reversed')->count());
    }

    private function failQuery(string $table, string $operation): void
    {
        $injected = false;
        DB::connection()->beforeExecuting(function (string $query) use ($table, $operation, &$injected): void {
            $sql = strtolower(ltrim($query));
            if (! $injected && str_starts_with($sql, $operation) && str_contains($sql, $table)) {
                $injected = true;
                throw new RuntimeException("injected {$table} {$operation} failure");
            }
        });
    }

    private function row(string $entityType, string $status = 'candidate'): array
    {
        $institution = Institution::create([
            'name' => 'Phase 1C-21 Canonical '.bin2hex(random_bytes(3)),
            'official_name' => 'Phase 1C-21 Canonical Institute',
            'slug' => 'phase-1c-21-'.bin2hex(random_bytes(4)),
            'status' => 'candidate',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => 'phase-1c-21-'.bin2hex(random_bytes(5)),
            'entity_type' => $entityType,
            'source_type' => 'manual',
            'status' => 'validated',
            'total_rows' => 1,
            'valid_rows' => 1,
            'candidate_rows' => 1,
            'received_at' => now(),
            'completed_at' => now(),
        ]);
        $row = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => $status,
            'match_state' => 'new_candidate',
            'payload' => ['name' => 'Phase 1C-21 candidate', 'slug' => 'phase-1c-21-candidate'],
            'normalized_payload' => ['name' => 'Phase 1C-21 candidate', 'slug' => 'phase-1c-21-candidate'],
        ]);

        return [$row, $institution, $batch];
    }

    private function promotableInstitutionRow(): array
    {
        [$row, $institution] = $this->row('institution', 'promoted');
        $row->update(['canonical_subject_type' => Institution::class, 'canonical_subject_id' => $institution->id]);
        IdentityResolutionDecision::create([
            'import_row_id' => $row->id,
            'institution_id' => null,
            'decision' => 'approved_new',
            'actor_type' => 'user',
            'actor_id' => 808,
            'reason' => 'Explicit approved-new identity decision.',
            'decided_at' => now(),
        ]);
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Phase 1C-21 publication evidence source',
            'url' => 'https://example.test/phase-1c-21-publication',
            'status' => 'active',
        ]);
        $evidence = Evidence::create([
            'source_id' => $source->id,
            'reference' => 'https://example.test/phase-1c-21-publication',
            'excerpt' => 'Official source evidence for publication eligibility.',
            'content_hash' => 'phase-1c-21-publication-'.$row->id,
            'observed_at' => now(),
            'status' => 'active',
        ]);
        Assertion::create([
            'evidence_id' => $evidence->id,
            'subject_type' => ImportRow::class,
            'subject_id' => $row->id,
            'attribute' => 'institution_identity',
            'value' => 'Phase 1C-21 candidate',
            'verification_state' => 'observed',
            'confidence' => 1,
        ]);

        return [$row, $institution];
    }
}

<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportPromotionEvent;
use App\Models\ImportReconciliationEvent;
use App\Models\ImportRow;
use App\Models\IdentityResolutionDecision;
use App\Models\SecurityAuditEvent;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class AppendOnlyAuditRetentionTest extends TestCase
{
    use RefreshDatabase;

    private function batchAndRow(): array
    {
        $batch = ImportBatch::create([
            'batch_key' => 'audit-retention-'.bin2hex(random_bytes(5)),
            'entity_type' => 'institution',
            'source_type' => 'automated_discovery',
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
            'status' => 'candidate',
            'match_state' => 'unresolved',
            'payload' => ['name' => 'Audit retention candidate'],
        ]);

        return [$batch, $row];
    }

    public function test_security_audit_event_cannot_be_updated(): void
    {
        $event = SecurityAuditEvent::create([
            'event_type' => 'audit_retention_test',
            'severity' => 'info',
            'description' => 'immutable event',
        ]);

        $this->expectException(RuntimeException::class);
        $event->update(['description' => 'rewritten']);
    }

    public function test_security_audit_event_cannot_be_deleted(): void
    {
        $event = SecurityAuditEvent::create([
            'event_type' => 'audit_retention_test',
            'severity' => 'info',
            'description' => 'retained event',
        ]);

        $this->expectException(RuntimeException::class);
        $event->delete();
    }

    public function test_promotion_event_cannot_be_updated_or_deleted(): void
    {
        [$batch, $row] = $this->batchAndRow();
        $event = ImportPromotionEvent::create([
            'import_row_id' => $row->id,
            'import_batch_id' => $batch->id,
            'entity_type' => 'institution',
            'event_type' => 'approved',
            'occurred_at' => now(),
        ]);

        try {
            $event->update(['event_type' => 'rewritten']);
            $this->fail('Audit event updates must be rejected by the model guard.');
        } catch (RuntimeException) {
            $this->assertDatabaseHas('import_promotion_events', ['id' => $event->id, 'event_type' => 'approved']);
        }

        $this->expectException(RuntimeException::class);
        $event->delete();
    }

    public function test_parent_row_and_batch_deletion_cannot_cascade_away_audit_history(): void
    {
        [$batch, $row] = $this->batchAndRow();
        $event = ImportPromotionEvent::create([
            'import_row_id' => $row->id,
            'import_batch_id' => $batch->id,
            'entity_type' => 'institution',
            'event_type' => 'approved',
            'occurred_at' => now(),
        ]);

        try {
            DB::table('import_rows')->where('id', $row->id)->delete();
            $this->fail('Deleting a row with retained audit history must be rejected.');
        } catch (QueryException) {
            $this->assertDatabaseHas('import_rows', ['id' => $row->id]);
            $this->assertDatabaseHas('import_promotion_events', ['id' => $event->id]);
        }

        $this->expectException(QueryException::class);
        DB::table('import_batches')->where('id', $batch->id)->delete();
    }

    public function test_transaction_rollback_removes_only_the_uncommitted_new_event(): void
    {
        SecurityAuditEvent::create([
            'event_type' => 'preexisting_audit_event',
            'severity' => 'info',
            'description' => 'must remain',
        ]);

        try {
            DB::transaction(function (): void {
                SecurityAuditEvent::create([
                    'event_type' => 'rolled_back_audit_event',
                    'severity' => 'info',
                    'description' => 'uncommitted',
                ]);

                throw new RuntimeException('rollback test');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('rollback test', $exception->getMessage());
        }

        $this->assertDatabaseCount('security_audit_events', 1);
        $this->assertDatabaseHas('security_audit_events', ['event_type' => 'preexisting_audit_event']);
        $this->assertDatabaseMissing('security_audit_events', ['event_type' => 'rolled_back_audit_event']);
    }

    public function test_identity_decision_allows_only_one_time_legacy_key_enrichment(): void
    {
        [$batch, $row] = $this->batchAndRow();
        $decision = IdentityResolutionDecision::create([
            'import_row_id' => $row->id,
            'institution_id' => null,
            'decision' => 'deferred',
            'actor_type' => 'system',
            'reason' => 'historical decision',
            'decided_at' => now(),
        ]);

        $decision->update(['decision_identity_key' => hash('sha256', 'legacy-decision-key')]);
        $this->assertNotNull($decision->fresh()->decision_identity_key);

        try {
            $decision->update(['reason' => 'rewritten historical reason']);
            $this->fail('Historical decision content must remain immutable.');
        } catch (RuntimeException) {
            $this->assertDatabaseHas('identity_resolution_decisions', [
                'id' => $decision->id,
                'reason' => 'historical decision',
            ]);
        }

        $this->expectException(RuntimeException::class);
        $decision->delete();
    }



    public function test_direct_sql_cannot_update_or_delete_append_only_audit_rows(): void
    {
        $event = SecurityAuditEvent::create([
            'event_type' => 'direct_sql_immutability_test',
            'severity' => 'info',
            'description' => 'immutable direct SQL record',
        ]);

        try {
            DB::table('security_audit_events')->where('id', $event->id)
                ->update(['description' => 'rewritten through SQL']);
            $this->fail('Direct SQL UPDATE must be rejected by the database trigger.');
        } catch (QueryException) {
            $this->assertDatabaseHas('security_audit_events', [
                'id' => $event->id,
                'description' => 'immutable direct SQL record',
            ]);
        }

        try {
            DB::table('security_audit_events')->where('id', $event->id)->delete();
            $this->fail('Direct SQL DELETE must be rejected by the database trigger.');
        } catch (QueryException) {
            $this->assertDatabaseHas('security_audit_events', ['id' => $event->id]);
        }
    }

    public function test_direct_sql_identity_key_enrichment_is_one_time_and_narrow(): void
    {
        [, $row] = $this->batchAndRow();
        $decision = IdentityResolutionDecision::create([
            'import_row_id' => $row->id,
            'institution_id' => null,
            'decision' => 'deferred',
            'actor_type' => 'system',
            'reason' => 'historical decision',
            'decided_at' => now(),
        ]);
        $key = hash('sha256', 'direct-sql-legacy-decision-key');

        try {
            DB::table('identity_resolution_decisions')
                ->where('id', $decision->id)
                ->update([
                    'id' => $decision->id + 100000,
                    'decision_identity_key' => $key,
                ]);
            $this->fail('The one-time key exception must not permit primary-key mutation.');
        } catch (QueryException) {
            $this->assertDatabaseHas('identity_resolution_decisions', [
                'id' => $decision->id,
                'decision_identity_key' => null,
            ]);
        }

        $updated = DB::table('identity_resolution_decisions')
            ->where('id', $decision->id)
            ->whereNull('decision_identity_key')
            ->update(['decision_identity_key' => $key]);

        $this->assertSame(1, $updated);
        $this->assertDatabaseHas('identity_resolution_decisions', [
            'id' => $decision->id,
            'decision_identity_key' => $key,
            'reason' => 'historical decision',
        ]);

        try {
            DB::table('identity_resolution_decisions')
                ->where('id', $decision->id)
                ->update(['decision_identity_key' => hash('sha256', 'second-enrichment')]);
            $this->fail('A second identity-key enrichment must be rejected.');
        } catch (QueryException) {
            $this->assertDatabaseHas('identity_resolution_decisions', [
                'id' => $decision->id,
                'decision_identity_key' => $key,
            ]);
        }

        try {
            DB::table('identity_resolution_decisions')
                ->where('id', $decision->id)
                ->update(['reason' => 'rewritten by direct SQL']);
            $this->fail('Unrelated identity decision fields must remain immutable.');
        } catch (QueryException) {
            $this->assertDatabaseHas('identity_resolution_decisions', [
                'id' => $decision->id,
                'reason' => 'historical decision',
            ]);
        }

        try {
            DB::table('identity_resolution_decisions')->where('id', $decision->id)->delete();
            $this->fail('Direct SQL DELETE of identity decisions must be rejected.');
        } catch (QueryException) {
            $this->assertDatabaseHas('identity_resolution_decisions', ['id' => $decision->id]);
        }
    }


    public function test_reconciliation_event_history_is_immutable_except_nullable_row_detachment(): void
    {
        [$batch, $row] = $this->batchAndRow();
        $event = ImportReconciliationEvent::create([
            'import_batch_id' => $batch->id,
            'import_row_id' => $row->id,
            'event_type' => 'multiple_identity_matches',
            'state' => 'open',
            'severity' => 'error',
            'description' => 'reconciliation history record',
            'metadata' => ['match_state' => 'conflict'],
            'occurred_at' => now(),
        ]);

        try {
            $event->update(['description' => 'rewritten through Eloquent']);
            $this->fail('Reconciliation event model updates must be rejected.');
        } catch (RuntimeException) {
            $this->assertDatabaseHas('import_reconciliation_events', [
                'id' => $event->id,
                'description' => 'reconciliation history record',
            ]);
        }

        try {
            DB::table('import_reconciliation_events')->where('id', $event->id)
                ->update(['description' => 'rewritten through direct SQL']);
            $this->fail('Direct SQL UPDATE of reconciliation history must be rejected.');
        } catch (QueryException) {
            $this->assertDatabaseHas('import_reconciliation_events', [
                'id' => $event->id,
                'description' => 'reconciliation history record',
                'import_row_id' => $row->id,
            ]);
        }

        try {
            DB::table('import_reconciliation_events')->where('id', $event->id)->delete();
            $this->fail('Direct SQL DELETE of reconciliation history must be rejected.');
        } catch (QueryException) {
            $this->assertDatabaseHas('import_reconciliation_events', ['id' => $event->id]);
        }

        $deletedRows = DB::table('import_rows')->where('id', $row->id)->delete();
        $this->assertSame(1, $deletedRows);
        $this->assertDatabaseHas('import_reconciliation_events', [
            'id' => $event->id,
            'import_batch_id' => $batch->id,
            'import_row_id' => null,
            'description' => 'reconciliation history record',
        ]);

        try {
            DB::table('import_batches')->where('id', $batch->id)->delete();
            $this->fail('Batch deletion must not remove reconciliation history.');
        } catch (QueryException) {
            $this->assertDatabaseHas('import_reconciliation_events', [
                'id' => $event->id,
                'import_batch_id' => $batch->id,
            ]);
        }
    }



    public function test_governed_execution_run_cannot_be_deleted_while_retained_events_exist(): void
    {
        $runId = sprintf(
            '%s-%s-%s-%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(6)),
        );
        $now = now();

        DB::table('governed_execution_runs')->insert([
            'id' => $runId,
            'task_key' => 'audit-retention-parent-delete-test',
            'owner_token' => sprintf(
                '%s-%s-%s-%s-%s',
                bin2hex(random_bytes(4)),
                bin2hex(random_bytes(2)),
                bin2hex(random_bytes(2)),
                bin2hex(random_bytes(2)),
                bin2hex(random_bytes(6)),
            ),
            'runner' => 'phpunit',
            'application_revision' => null,
            'status' => 'completed',
            'started_at' => $now,
            'heartbeat_at' => $now,
            'finished_at' => $now,
            'failure_code' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $eventId = DB::table('governed_execution_run_events')->insertGetId([
            'run_id' => $runId,
            'event_type' => 'completed',
            'occurred_at' => $now,
            'failure_code' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        try {
            DB::table('governed_execution_runs')->where('id', $runId)->delete();
            $this->fail('Deleting a governed run must not cascade-delete retained audit events.');
        } catch (QueryException) {
            $this->assertDatabaseHas('governed_execution_runs', ['id' => $runId]);
            $this->assertDatabaseHas('governed_execution_run_events', [
                'id' => $eventId,
                'run_id' => $runId,
                'event_type' => 'completed',
            ]);
        }
    }

}

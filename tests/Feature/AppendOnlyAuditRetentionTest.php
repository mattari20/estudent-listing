<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportPromotionEvent;
use App\Models\ImportRow;
use App\Models\IdentityResolutionDecision;
use App\Models\SecurityAuditEvent;
use Illuminate\Database\QueryException;
use RuntimeException;
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


}

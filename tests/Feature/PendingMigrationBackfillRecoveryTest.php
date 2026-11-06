<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PendingMigrationBackfillRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_reverification_identity_backfill_resumes_without_duplicate_mapping(): void
    {
        $now = now();
        $queueId = DB::table('data_reverification_queue')->insertGetId([
            'subject_type' => 'institution',
            'subject_id' => 987654,
            'attribute' => 'description',
            'reason' => 'isolated migration recovery test',
            'priority' => 'normal',
            'status' => 'open',
            'source_id' => null,
            'evidence_id' => null,
            'observed_at' => $now,
            'due_at' => null,
            'assigned_to_type' => null,
            'assigned_to_id' => null,
            'notes' => null,
            'resolved_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $migration = require database_path('migrations/2026_11_03_000007_create_data_reverification_queue_identities_table.php');
        $migration->up();
        $migration->up();

        $identityKey = hash('sha256', json_encode([
            'institution',
            987654,
            'description',
        ], JSON_THROW_ON_ERROR));

        $this->assertDatabaseCount('data_reverification_queue_identities', 1);
        $this->assertDatabaseHas('data_reverification_queue_identities', [
            'identity_key' => $identityKey,
            'subject_type' => 'institution',
            'subject_id' => 987654,
            'attribute' => 'description',
            'active_queue_id' => $queueId,
        ]);
    }

    public function test_reverification_identity_backfill_refuses_conflicting_existing_mapping(): void
    {
        $now = now();
        $firstQueueId = DB::table('data_reverification_queue')->insertGetId([
            'subject_type' => 'institution',
            'subject_id' => 987655,
            'attribute' => 'website',
            'reason' => 'isolated conflicting identity test',
            'priority' => 'normal',
            'status' => 'open',
            'source_id' => null,
            'evidence_id' => null,
            'observed_at' => $now,
            'due_at' => null,
            'assigned_to_type' => null,
            'assigned_to_id' => null,
            'notes' => null,
            'resolved_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $otherQueueId = DB::table('data_reverification_queue')->insertGetId([
            'subject_type' => 'institution',
            'subject_id' => 987656,
            'attribute' => 'website',
            'reason' => 'isolated conflicting identity target',
            'priority' => 'normal',
            'status' => 'open',
            'source_id' => null,
            'evidence_id' => null,
            'observed_at' => $now,
            'due_at' => null,
            'assigned_to_type' => null,
            'assigned_to_id' => null,
            'notes' => null,
            'resolved_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $identityKey = hash('sha256', json_encode([
            'institution',
            987655,
            'website',
        ], JSON_THROW_ON_ERROR));

        DB::table('data_reverification_queue_identities')->insert([
            'identity_key' => $identityKey,
            'subject_type' => 'institution',
            'subject_id' => 987655,
            'attribute' => 'website',
            'active_queue_id' => $otherQueueId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $migration = require database_path('migrations/2026_11_03_000007_create_data_reverification_queue_identities_table.php');

        $this->expectException(\\RuntimeException::class);
        $this->expectExceptionMessage('conflicts with its source queue row');
        $migration->up();
    }

    public function test_reverification_identity_backfill_rejects_duplicate_open_queue_identity_groups(): void
    {
        $now = now();
        foreach ([987657, 987657] as $subjectId) {
            DB::table('data_reverification_queue')->insert([
                'subject_type' => 'institution',
                'subject_id' => $subjectId,
                'attribute' => 'campus_name',
                'reason' => 'isolated duplicate identity test',
                'priority' => 'normal',
                'status' => 'open',
                'source_id' => null,
                'evidence_id' => null,
                'observed_at' => $now,
                'due_at' => null,
                'assigned_to_type' => null,
                'assigned_to_id' => null,
                'notes' => null,
                'resolved_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $migration = require database_path('migrations/2026_11_03_000007_create_data_reverification_queue_identities_table.php');

        $this->expectException(\\RuntimeException::class);
        $this->expectExceptionMessage('duplicate open reverification queue identities');
        $migration->up();
    }

    public function test_monitoring_alert_identity_backfill_refuses_conflicting_existing_mapping(): void
    {
        $now = now();
        $dedupeKey = 'migration-conflict-'.bin2hex(random_bytes(8));
        $firstAlertId = DB::table('monitoring_alerts')->insertGetId([
            'source_monitoring_policy_id' => null,
            'source_id' => null,
            'alert_type' => 'migration_recovery_conflict',
            'severity' => 'info',
            'status' => 'open',
            'dedupe_key' => $dedupeKey,
            'message' => 'Isolated conflicting identity test',
            'details' => null,
            'first_detected_at' => $now,
            'last_detected_at' => $now,
            'acknowledged_at' => null,
            'resolved_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $otherAlertId = DB::table('monitoring_alerts')->insertGetId([
            'source_monitoring_policy_id' => null,
            'source_id' => null,
            'alert_type' => 'migration_recovery_conflict_target',
            'severity' => 'info',
            'status' => 'resolved',
            'dedupe_key' => $dedupeKey.'-target',
            'message' => 'Isolated alternate mapping target',
            'details' => null,
            'first_detected_at' => $now,
            'last_detected_at' => $now,
            'acknowledged_at' => null,
            'resolved_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('monitoring_alert_identities')->insert([
            'dedupe_key' => $dedupeKey,
            'active_alert_id' => $otherAlertId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $migration = require database_path('migrations/2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency.php');

        $this->expectException(\\RuntimeException::class);
        $this->expectExceptionMessage('points to a different alert');
        $migration->up();
    }

    public function test_monitoring_alert_identity_backfill_rejects_duplicate_open_dedupe_keys(): void
    {
        $now = now();
        $dedupeKey = 'migration-duplicate-'.bin2hex(random_bytes(8));
        foreach (['first', 'second'] as $message) {
            DB::table('monitoring_alerts')->insert([
                'source_monitoring_policy_id' => null,
                'source_id' => null,
                'alert_type' => 'migration_recovery_duplicate',
                'severity' => 'info',
                'status' => 'open',
                'dedupe_key' => $dedupeKey,
                'message' => 'Isolated duplicate alert '.$message,
                'details' => null,
                'first_detected_at' => $now,
                'last_detected_at' => $now,
                'acknowledged_at' => null,
                'resolved_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $migration = require database_path('migrations/2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency.php');

        $this->expectException(\\RuntimeException::class);
        $this->expectExceptionMessage('duplicate open monitoring alert dedupe keys');
        $migration->up();
    }

    public function test_monitoring_alert_identity_backfill_resumes_without_duplicate_mapping(): void
    {
        $now = now();
        $alertId = DB::table('monitoring_alerts')->insertGetId([
            'source_monitoring_policy_id' => null,
            'source_id' => null,
            'alert_type' => 'migration_recovery_test',
            'severity' => 'info',
            'status' => 'open',
            'dedupe_key' => 'migration-recovery-'.bin2hex(random_bytes(8)),
            'message' => 'Isolated migration backfill recovery test',
            'details' => null,
            'first_detected_at' => $now,
            'last_detected_at' => $now,
            'acknowledged_at' => null,
            'resolved_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $dedupeKey = DB::table('monitoring_alerts')->where('id', $alertId)->value('dedupe_key');
        $migration = require database_path('migrations/2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency.php');
        $migration->up();
        $migration->up();

        $this->assertDatabaseCount('monitoring_alert_identities', 1);
        $this->assertDatabaseHas('monitoring_alert_identities', [
            'dedupe_key' => $dedupeKey,
            'active_alert_id' => $alertId,
        ]);
    }
}

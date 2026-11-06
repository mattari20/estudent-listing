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

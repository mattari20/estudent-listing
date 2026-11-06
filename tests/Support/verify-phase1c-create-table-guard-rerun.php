<?php

declare(strict_types=1);

use Illuminate\Contracts\\Console\Kernel;
use Illuminate\Support\\Facades\DB;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (DB::getDriverName() !== 'mysql') {
    fwrite(STDERR, "This migration rerun proof requires the isolated MySQL/MariaDB service.\n");
    exit(2);
}

$migrations = [
    '2026_10_17_000001_create_password_reset_tokens_table.php',
    '2026_10_17_000002_add_must_change_password_to_users_table.php',
    '2026_10_23_000001_add_duplicate_rows_to_import_batches_table.php',
    '2026_10_25_000001_create_structured_enrichment_promotion_events_table.php',
    '2026_11_03_000001_create_governed_execution_leases_table.php',
    '2026_11_03_000002_create_governed_execution_runs_table.php',
    '2026_11_03_000003_add_source_retrieval_idempotency_key.php',
    '2026_11_03_000004_add_snapshot_and_observation_idempotency.php',
    '2026_11_03_000005_add_discovery_outcome_idempotency_key.php',
    '2026_11_03_000006_add_change_event_source_link_identity.php',
    '2026_11_03_000007_create_data_reverification_queue_identities_table.php',
    '2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency.php',
    '2026_11_04_000010_create_monitoring_policy_run_checkpoints.php',
    '2026_11_04_000011_add_discovery_candidate_persistence_identity_keys.php',
    '2026_11_04_000012_add_identity_conflict_persistence_keys_and_events.php',
    '2026_11_04_000013_create_source_snapshot_reconciliation_requests_table.php',
    '2026_11_04_000014_enforce_append_only_audit_retention.php',
    '2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat.php',
];

foreach ($migrations as $file) {
    $migration = require $root.'/database/migrations/'.$file;
    $migration->up();
    fwrite(STDOUT, 'rerun_definition_check='.$file.':PASS'.PHP_EOL);
}

fwrite(STDOUT, "Reapplied all 18 migrations successfully against the already-created schema on the isolated database.\n");

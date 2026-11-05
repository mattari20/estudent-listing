<?php
declare(strict_types=1);

/**
 * Phase 1C Test 1B preflight.
 * SELECT-only inspection in a read-only transaction. No migrations, DDL, DML,
 * Artisan commands, or persistent output.
 */
require getcwd().'/vendor/autoload.php';

$cache = getcwd().'/bootstrap/cache/config.php';
if (is_file($cache)) {
    $app = require $cache;
    if (!is_array($app) || !is_array($app['database'] ?? null)) {
        fwrite(STDERR, "laravel_config_cache=INVALID\n"); exit(10);
    }
    $dbConfig = $app['database'];
    $configSource = 'laravel_config_cache';
} else {
    if (!class_exists(\Dotenv\Dotenv::class)) {
        fwrite(STDERR, "dotenv_library=UNAVAILABLE\n"); exit(10);
    }
    \Dotenv\Dotenv::createImmutable(getcwd())->safeLoad();
    $dbConfig = require getcwd().'/config/database.php';
    $configSource = 'dotenv_plus_config_database';
}
$name = (string)($dbConfig['default'] ?? 'mysql');
$conn = $dbConfig['connections'][$name] ?? null;
if (!is_array($conn) || ($conn['driver'] ?? null) !== 'mysql') {
    fwrite(STDERR, "effective_database_driver=UNSUPPORTED_OR_MISSING\n"); exit(11);
}
if (!empty($conn['url'])) {
    $parser = \Illuminate\Database\ConfigurationUrlParser::class;
    if (!class_exists($parser)) {
        fwrite(STDERR, "laravel_database_url_parser=UNAVAILABLE\n"); exit(12);
    }
    $conn = (new $parser())->parseConfiguration($conn);
}
$host = (string)($conn['host'] ?? '');
$db = (string)($conn['database'] ?? '');
$user = (string)($conn['username'] ?? '');
$pass = (string)($conn['password'] ?? '');
$port = (string)($conn['port'] ?? '3306');
$socket = (string)($conn['unix_socket'] ?? '');
if ($db === '' || $user === '' || (!$socket && ($host === '' || !ctype_digit($port)))) {
    fwrite(STDERR, "effective_database_connection=INCOMPLETE\n"); exit(13);
}

$pdo = null;
$open = false;
try {
    $dsn = $socket !== ''
        ? 'mysql:unix_socket='.$socket.';dbname='.$db.';charset=utf8mb4'
        : 'mysql:host='.$host.';port='.$port.';dbname='.$db.';charset=utf8mb4';
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10,
    ]);
    $pdo->exec('SET TRANSACTION READ ONLY');
    $pdo->beginTransaction();
    $open = true;

    $identity = $pdo->query('SELECT DATABASE() AS d, @@hostname AS h, CURRENT_USER() AS u')->fetch();
    $fingerprint = hash('sha256', implode("\0", [
        strtolower((string)($identity['h'] ?? '')),
        strtolower((string)($identity['d'] ?? '')),
        strtolower((string)($identity['u'] ?? '')),
    ]));
    echo "phase_1c_test_1b_preflight=STARTED\n";
    echo "effective_database_config_source={$configSource}\n";
    echo "effective_database_connection_name={$name}\n";
    echo "effective_database_fingerprint={$fingerprint}\n";

    $tables = [
        'migrations','institutions','sources','source_monitoring_policies',
        'monitoring_policy_runs','monitoring_policy_run_sources','governed_execution_runs',
        'source_retrieval_runs','source_change_observations','monitoring_alert_identities',
        'monitoring_alerts','security_audit_events','verification_decisions',
        'provenance_review_events','import_promotion_decisions','import_promotion_events',
        'reverification_events','structured_enrichment_promotion_events',
        'official_source_refresh_events','import_conflict_events','import_reconciliation_events',
        'governed_execution_run_events','identity_resolution_decisions','import_rows',
        'import_batches','assertions','data_reverification_queue','enrichment_import_rows',
        'import_conflicts',
    ];
    $tableStmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $present = [];
    $missing = [];
    foreach ($tables as $table) {
        $tableStmt->execute([$table]);
        if ((int)$tableStmt->fetchColumn() === 1) $present[$table] = true;
        else $missing[] = $table;
    }
    echo 'required_table_checks='.count($tables).';missing='.count($missing)."\n";
    foreach ($missing as $table) echo "missing_required_table={$table}\n";

    // Aggregate counts only; no production row values are read or emitted.
    foreach ([
        'monitoring_policy_runs','monitoring_policy_run_sources','monitoring_alerts',
        'monitoring_alert_identities','governed_execution_runs','governed_execution_run_events',
        'security_audit_events','identity_resolution_decisions',
    ] as $table) {
        if (isset($present[$table])) {
            $count = (int)$pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
            echo "row_count_{$table}={$count}\n";
        } else echo "row_count_{$table}=NOT_CHECKED_TABLE_MISSING\n";
    }

    $orphans = [
        '014_identity_decisions_without_import_rows' => "SELECT COUNT(*) FROM identity_resolution_decisions d LEFT JOIN import_rows r ON r.id=d.import_row_id WHERE r.id IS NULL",
        '014_verification_decisions_without_assertions' => "SELECT COUNT(*) FROM verification_decisions d LEFT JOIN assertions a ON a.id=d.assertion_id WHERE a.id IS NULL",
        '014_reconciliation_events_without_batches' => "SELECT COUNT(*) FROM import_reconciliation_events e LEFT JOIN import_batches b ON b.id=e.import_batch_id WHERE b.id IS NULL",
        '014_promotion_decisions_without_rows' => "SELECT COUNT(*) FROM import_promotion_decisions d LEFT JOIN import_rows r ON r.id=d.import_row_id WHERE r.id IS NULL",
        '014_promotion_events_without_rows' => "SELECT COUNT(*) FROM import_promotion_events e LEFT JOIN import_rows r ON r.id=e.import_row_id WHERE r.id IS NULL",
        '014_promotion_events_without_batches' => "SELECT COUNT(*) FROM import_promotion_events e LEFT JOIN import_batches b ON b.id=e.import_batch_id WHERE b.id IS NULL",
        '014_reverification_events_without_queue_rows' => "SELECT COUNT(*) FROM reverification_events e LEFT JOIN data_reverification_queue q ON q.id=e.queue_id WHERE q.id IS NULL",
        '014_enrichment_events_without_rows' => "SELECT COUNT(*) FROM structured_enrichment_promotion_events e LEFT JOIN enrichment_import_rows r ON r.id=e.enrichment_import_row_id WHERE r.id IS NULL",
        '014_enrichment_events_without_batches' => "SELECT COUNT(*) FROM structured_enrichment_promotion_events e LEFT JOIN import_batches b ON b.id=e.import_batch_id WHERE b.id IS NULL",
        '014_conflict_events_without_conflicts' => "SELECT COUNT(*) FROM import_conflict_events e LEFT JOIN import_conflicts c ON c.id=e.import_conflict_id WHERE c.id IS NULL",
        '014_governed_events_without_runs' => "SELECT COUNT(*) FROM governed_execution_run_events e LEFT JOIN governed_execution_runs r ON r.id=e.run_id WHERE r.id IS NULL",
        '015_monitoring_runs_without_policies' => "SELECT COUNT(*) FROM monitoring_policy_runs r LEFT JOIN source_monitoring_policies p ON p.id=r.source_monitoring_policy_id WHERE p.id IS NULL",
        '015_monitoring_runs_without_execution_runs' => "SELECT COUNT(*) FROM monitoring_policy_runs r LEFT JOIN governed_execution_runs g ON g.id=r.execution_run_id WHERE r.execution_run_id IS NOT NULL AND g.id IS NULL",
        '015_monitoring_sources_without_runs' => "SELECT COUNT(*) FROM monitoring_policy_run_sources s LEFT JOIN monitoring_policy_runs r ON r.id=s.monitoring_policy_run_id WHERE r.id IS NULL",
        '015_monitoring_sources_without_sources' => "SELECT COUNT(*) FROM monitoring_policy_run_sources s LEFT JOIN sources x ON x.id=s.source_id WHERE x.id IS NULL",
        '015_monitoring_sources_without_retrieval_runs' => "SELECT COUNT(*) FROM monitoring_policy_run_sources s LEFT JOIN source_retrieval_runs r ON r.id=s.retrieval_run_id WHERE s.retrieval_run_id IS NOT NULL AND r.id IS NULL",
        '015_monitoring_sources_without_observations' => "SELECT COUNT(*) FROM monitoring_policy_run_sources s LEFT JOIN source_change_observations o ON o.id=s.observation_id WHERE s.observation_id IS NOT NULL AND o.id IS NULL",
        '015_alert_identities_without_active_alerts' => "SELECT COUNT(*) FROM monitoring_alert_identities i LEFT JOIN monitoring_alerts a ON a.id=i.active_alert_id WHERE i.active_alert_id IS NOT NULL AND a.id IS NULL",
    ];
    $orphanFailures = 0;
    foreach ($orphans as $key => $sql) {
        try {
            $count = (int)$pdo->query($sql)->fetchColumn();
            if ($count > 0) $orphanFailures++;
            echo "orphan_check_{$key}={$count}\n";
        } catch (Throwable $e) {
            $orphanFailures++;
            echo "orphan_check_{$key}=NOT_CHECKED_DEPENDENCY_OR_COLUMN_MISSING\n";
        }
    }

    $migrations = [
        '2026_11_04_000010_create_monitoring_policy_run_checkpoints',
        '2026_11_04_000014_enforce_append_only_audit_retention',
        '2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat',
    ];
    $ledger = [];
    $ledgerStmt = isset($present['migrations'])
        ? $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration=?')
        : null;
    foreach ($migrations as $migration) {
        if (!($ledgerStmt instanceof PDOStatement)) {
            $ledger[$migration] = -1;
            echo "migration_ledger_{$migration}=NOT_CHECKED_MIGRATIONS_TABLE_MISSING\n";
            continue;
        }
        $ledgerStmt->execute([$migration]);
        $ledger[$migration] = (int)$ledgerStmt->fetchColumn();
        echo "migration_ledger_{$migration}={$ledger[$migration]}\n";
    }

    $auditTables = [
        'security_audit_events','verification_decisions','provenance_review_events',
        'import_promotion_decisions','import_promotion_events','reverification_events',
        'structured_enrichment_promotion_events','official_source_refresh_events',
        'import_conflict_events','import_reconciliation_events','governed_execution_run_events',
    ];
    $triggerNames = [];
    foreach ($auditTables as $table) {
        $suffix = substr(hash('sha256', $table), 0, 16);
        $triggerNames[] = 'audit_immut_'.$suffix.'_u';
        $triggerNames[] = 'audit_immut_'.$suffix.'_d';
    }
    $triggerNames[] = 'identity_decision_update_guard';
    $triggerNames[] = 'identity_decision_delete_guard';
    $triggerStmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME=?');
    $triggerPresent = 0;
    foreach ($triggerNames as $trigger) {
        $triggerStmt->execute([$trigger]);
        if ((int)$triggerStmt->fetchColumn() === 1) $triggerPresent++;
    }
    echo 'expected_audit_triggers='.count($triggerNames).";present={$triggerPresent};missing=".(count($triggerNames)-$triggerPresent)."\n";

    // Migration 000014 replaces eleven cascading relationships with RESTRICT rules.
    $auditFkDefs = [
        ['identity_resolution_decisions','import_row_id','identity_decision_row_restrict_fk','import_rows'],
        ['verification_decisions','assertion_id','verification_decision_assertion_restrict_fk','assertions'],
        ['import_reconciliation_events','import_batch_id','import_reconciliation_batch_restrict_fk','import_batches'],
        ['import_promotion_decisions','import_row_id','import_promotion_decision_row_restrict_fk','import_rows'],
        ['import_promotion_events','import_row_id','import_promotion_event_row_restrict_fk','import_rows'],
        ['import_promotion_events','import_batch_id','import_promotion_event_batch_restrict_fk','import_batches'],
        ['reverification_events','queue_id','reverification_event_queue_restrict_fk','data_reverification_queue'],
        ['structured_enrichment_promotion_events','enrichment_import_row_id','structured_enrichment_row_restrict_fk','enrichment_import_rows'],
        ['structured_enrichment_promotion_events','import_batch_id','structured_enrichment_batch_restrict_fk','import_batches'],
        ['import_conflict_events','import_conflict_id','import_conflict_event_restrict_fk','import_conflicts'],
        ['governed_execution_run_events','run_id','governed_run_event_restrict_fk','governed_execution_runs'],
    ];
    $auditFkStmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? AND CONSTRAINT_NAME=? AND REFERENCED_TABLE_NAME=?');
    $auditFkPresent = 0;
    foreach ($auditFkDefs as [$table,$column,$fk,$parent]) {
        $auditFkStmt->execute([$table,$column,$fk,$parent]);
        if ((int)$auditFkStmt->fetchColumn() === 1) $auditFkPresent++;
    }
    echo 'expected_audit_restrict_foreign_keys='.count($auditFkDefs).";present={$auditFkPresent};missing=".(count($auditFkDefs)-$auditFkPresent)."\n";

    $fkDefs = [
        ['monitoring_policy_runs','source_monitoring_policy_id','mp_run_policy_fk','source_monitoring_policies'],
        ['monitoring_policy_runs','execution_run_id','mp_run_exec_fk','governed_execution_runs'],
        ['monitoring_policy_run_sources','monitoring_policy_run_id','mp_source_run_fk','monitoring_policy_runs'],
        ['monitoring_policy_run_sources','source_id','mp_source_source_fk','sources'],
        ['monitoring_policy_run_sources','retrieval_run_id','mp_source_retrieval_fk','source_retrieval_runs'],
        ['monitoring_policy_run_sources','observation_id','mp_source_observation_fk','source_change_observations'],
        ['monitoring_alert_identities','active_alert_id','mai_active_alert_fk','monitoring_alerts'],
    ];
    $fkStmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? AND CONSTRAINT_NAME=? AND REFERENCED_TABLE_NAME=?');
    $fkPresent = 0;
    foreach ($fkDefs as [$table,$column,$fk,$parent]) {
        $fkStmt->execute([$table,$column,$fk,$parent]);
        if ((int)$fkStmt->fetchColumn() === 1) $fkPresent++;
    }
    echo 'expected_monitoring_foreign_keys='.count($fkDefs).";present={$fkPresent};missing=".(count($fkDefs)-$fkPresent)."\n";

    $heartbeatStmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $heartbeatStmt->execute(['monitoring_policy_runs','heartbeat_at']);
    $heartbeat = (int)$heartbeatStmt->fetchColumn() === 1;
    echo 'monitoring_policy_runs.heartbeat_at='.($heartbeat ? 'PRESENT' : 'MISSING')."\n";

    $duplicateLedger = count(array_filter($ledger, static fn(int $n): bool => $n > 1)) > 0;
    $checkpointTablesPresent = isset($present['monitoring_policy_runs']) && isset($present['monitoring_policy_run_sources']);
    $anyCheckpointTablePresent = isset($present['monitoring_policy_runs']) || isset($present['monitoring_policy_run_sources']);
    $partial010 = ($ledger[$migrations[0]] === 0 && $anyCheckpointTablePresent)
        || ($ledger[$migrations[0]] === 1 && !$checkpointTablesPresent);
    $anyAuditObjectPresent = $triggerPresent > 0 || $auditFkPresent > 0;
    $allAuditMigrationObjectsPresent = $triggerPresent === count($triggerNames) && $auditFkPresent === count($auditFkDefs);
    $partial014 = ($ledger[$migrations[1]] === 0 && $anyAuditObjectPresent)
        || ($ledger[$migrations[1]] === 1 && !$allAuditMigrationObjectsPresent);
    $anyMonitoringObjectPresent = $fkPresent > 0 || $heartbeat;
    $allMonitoringObjectsPresent = $fkPresent === count($fkDefs) && $heartbeat;
    $partial015 = ($ledger[$migrations[2]] === 0 && $anyMonitoringObjectPresent)
        || ($ledger[$migrations[2]] === 1 && !$allMonitoringObjectsPresent);
    $partial = $duplicateLedger || $partial010 || $partial014 || $partial015;
    echo 'partial_application_detected='.($partial ? 'true' : 'false')."\n";

    $allLedger = count(array_filter($ledger, static fn(int $n): bool => $n === 1)) === 3;
    $allObjects = $triggerPresent === count($triggerNames) && $fkPresent === count($fkDefs) && $heartbeat;
    $allAuditObjects = $triggerPresent === count($triggerNames) && $auditFkPresent === count($auditFkDefs);
    if ($allLedger && $allObjects && $allAuditObjects) $classification = 'ALREADY_APPLIED_VERIFY_CONSISTENCY';
    elseif ($partial) $classification = 'BLOCKED_PARTIAL_APPLICATION_REQUIRES_RECONCILIATION';
    elseif (count($missing) > 0 || $orphanFailures > 0) $classification = 'BLOCKED_MISSING_DEPENDENCIES_OR_ORPHANS';
    else $classification = 'PREFLIGHT_READY_FOR_REVIEW_NOT_MIGRATION_AUTHORIZATION';

    echo "preflight_classification={$classification}\n";
    echo "production_database_changed=false\n";
    echo "production_application_files_changed=false\n";
    $pdo->rollBack();
    $open = false;
    echo "read_only_transaction=ROLLED_BACK\n";
    echo "phase_1c_test_1b_preflight=COMPLETE\n";
} catch (Throwable $error) {
    if ($open && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, 'preflight_error='.get_class($error)."\n");
    fwrite(STDERR, "phase_1c_test_1b_preflight=FAIL\n");
    exit(21);
}

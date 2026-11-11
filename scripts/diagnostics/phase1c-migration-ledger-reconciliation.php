<?php
declare(strict_types=1);

/**
 * Phase 1C migration-ledger reconciliation.
 * Reads only selected rows from Laravel's migrations ledger plus MySQL
 * information_schema metadata. No other application-table rows are read.
 * No DDL, DML, Artisan, migrations, or persistent output.
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

$migrations = [
    '2026_10_17_000001_create_password_reset_tokens_table' => [
        'tables' => ['password_reset_tokens'],
        'columns' => ['password_reset_tokens.email', 'password_reset_tokens.token', 'password_reset_tokens.created_at'],
    ],
    '2026_10_17_000002_add_must_change_password_to_users_table' => [
        'columns' => ['users.must_change_password'],
    ],
    '2026_10_23_000001_add_duplicate_rows_to_import_batches_table' => [
        'columns' => ['import_batches.duplicate_rows'],
    ],
    '2026_10_25_000001_create_structured_enrichment_promotion_events_table' => [
        'tables' => ['structured_enrichment_promotion_events'],
        'columns' => [
            'structured_enrichment_promotion_events.enrichment_import_row_id',
            'structured_enrichment_promotion_events.import_batch_id',
            'structured_enrichment_promotion_events.occurred_at',
        ],
    ],
    '2026_11_03_000001_create_governed_execution_leases_table' => [
        'tables' => ['governed_execution_leases'],
        'columns' => ['governed_execution_leases.task_key', 'governed_execution_leases.owner_token', 'governed_execution_leases.run_id', 'governed_execution_leases.expires_at'],
    ],
    '2026_11_03_000002_create_governed_execution_runs_table' => [
        'tables' => ['governed_execution_runs', 'governed_execution_run_events'],
        'columns' => ['governed_execution_runs.id', 'governed_execution_run_events.run_id', 'governed_execution_run_events.event_type', 'governed_execution_run_events.occurred_at'],
    ],
    '2026_11_03_000003_add_source_retrieval_idempotency_key' => [
        'columns' => ['source_retrieval_runs.idempotency_key'],
    ],
    '2026_11_03_000004_add_snapshot_and_observation_idempotency' => [
        'columns' => ['source_snapshots.capture_key', 'source_change_observations.current_snapshot_id'],
    ],
    '2026_11_03_000005_add_discovery_outcome_idempotency_key' => [
        'columns' => ['institute_discovery_run_outcomes.idempotency_key'],
    ],
    '2026_11_03_000006_add_change_event_source_link_identity' => [
        'columns' => ['change_events.source_change_assertion_link_id'],
    ],
    '2026_11_03_000007_create_data_reverification_queue_identities_table' => [
        'tables' => ['data_reverification_queue_identities'],
        'columns' => [
            'data_reverification_queue_identities.identity_key',
            'data_reverification_queue_identities.subject_type',
            'data_reverification_queue_identities.subject_id',
            'data_reverification_queue_identities.active_queue_id',
        ],
    ],
    '2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency' => [
        'tables' => ['monitoring_alert_identities'],
        'columns' => ['security_audit_events.monitoring_idempotency_key'],
    ],
    '2026_11_04_000010_create_monitoring_policy_run_checkpoints' => [
        'tables' => ['monitoring_policy_runs', 'monitoring_policy_run_sources'],
        'columns' => [
            'monitoring_policy_runs.source_monitoring_policy_id',
            'monitoring_policy_runs.execution_run_id',
            'monitoring_policy_runs.started_at',
            'monitoring_policy_run_sources.monitoring_policy_run_id',
            'monitoring_policy_run_sources.source_id',
            'monitoring_policy_run_sources.retrieval_run_id',
            'monitoring_policy_run_sources.observation_id',
        ],
    ],
    '2026_11_04_000011_add_discovery_candidate_persistence_identity_keys' => [
        'columns' => [
            'import_batches.discovery_identity_key',
            'evidence.discovery_identity_key',
            'import_rows.discovery_identity_key',
            'assertions.discovery_identity_key',
        ],
    ],
    '2026_11_04_000012_add_identity_conflict_persistence_keys_and_events' => [
        'tables' => ['import_conflict_events'],
        'columns' => [
            'identity_match_candidates.identity_key',
            'identity_resolution_decisions.decision_identity_key',
            'import_conflicts.identity_key',
        ],
    ],
    '2026_11_04_000013_create_source_snapshot_reconciliation_requests_table' => [
        'tables' => ['source_snapshot_reconciliation_requests'],
        'columns' => [
            'source_snapshot_reconciliation_requests.identity_key',
            'source_snapshot_reconciliation_requests.source_id',
            'source_snapshot_reconciliation_requests.triggering_snapshot_id',
            'source_snapshot_reconciliation_requests.current_snapshot_id',
            'source_snapshot_reconciliation_requests.expected_previous_snapshot_id',
            'source_snapshot_reconciliation_requests.recorded_previous_snapshot_id',
        ],
    ],
    '2026_11_04_000014_enforce_append_only_audit_retention' => [
        'triggers' => true,
    ],
    '2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat' => [
        'columns' => ['monitoring_policy_runs.heartbeat_at'],
        'foreign_keys' => [
            'mp_run_policy_fk','mp_run_exec_fk','mp_source_run_fk','mp_source_source_fk',
            'mp_source_retrieval_fk','mp_source_observation_fk','mai_active_alert_fk',
        ],
    ],
];

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

    $tableExists = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?'
    );
    $columnExists = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?'
    );
    $fkExists = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_TYPE="FOREIGN KEY" AND CONSTRAINT_NAME=?'
    );
    $triggerExists = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME=?'
    );

    $tableExists->execute(['migrations']);
    if ((int)$tableExists->fetchColumn() !== 1) {
        throw new RuntimeException('Laravel migrations ledger table is missing.');
    }
    $ledgerColumns = $pdo->query(
        'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="migrations"'
    )->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('migration', $ledgerColumns, true)) {
        throw new RuntimeException('Laravel migrations ledger has no migration column.');
    }

    $names = array_keys($migrations);
    $placeholders = implode(',', array_fill(0, count($names), '?'));
    $ledgerStmt = $pdo->prepare('SELECT migration, batch FROM migrations WHERE migration IN ('.$placeholders.') ORDER BY migration');
    $ledgerStmt->execute($names);
    $ledgerRows = $ledgerStmt->fetchAll();
    $ledger = [];
    foreach ($ledgerRows as $row) {
        $ledger[(string)$row['migration']][] = $row['batch'];
    }

    echo "phase_1c_migration_ledger_reconciliation=STARTED\n";
    echo "effective_database_config_source={$configSource}\n";
    echo "effective_database_connection_name={$name}\n";
    echo "effective_database_fingerprint={$fingerprint}\n";
    echo "ledger_rows_read=".count($ledgerRows)."\n";
    echo "application_rows_read_scope=selected_migrations_ledger_rows_only\n";

    $migrationFailures = 0;
    $ledgerDuplicates = 0;
    foreach ($migrations as $migration => $requirements) {
        $batches = $ledger[$migration] ?? [];
        $recorded = $batches !== [];
        $duplicateCount = max(0, count($batches) - 1);
        $ledgerDuplicates += $duplicateCount;
        $objects = [];
        foreach (($requirements['tables'] ?? []) as $table) {
            $tableExists->execute([$table]);
            $objects[] = $table.':'.(((int)$tableExists->fetchColumn() === 1) ? 'PRESENT' : 'MISSING');
        }
        foreach (($requirements['columns'] ?? []) as $qualified) {
            [$table, $column] = explode('.', $qualified, 2);
            $columnExists->execute([$table, $column]);
            $objects[] = $qualified.':'.(((int)$columnExists->fetchColumn() === 1) ? 'PRESENT' : 'MISSING');
        }
        foreach (($requirements['foreign_keys'] ?? []) as $fk) {
            $fkExists->execute([$fk]);
            $objects[] = 'foreign_key.'.$fk.':'.(((int)$fkExists->fetchColumn() === 1) ? 'PRESENT' : 'MISSING');
        }
        if (!empty($requirements['triggers'])) {
            $triggerNames = [];
            foreach ([
                'security_audit_events','verification_decisions','provenance_review_events',
                'import_promotion_decisions','import_promotion_events','reverification_events',
                'structured_enrichment_promotion_events','official_source_refresh_events',
                'import_conflict_events','import_reconciliation_events','governed_execution_run_events',
            ] as $table) {
                $suffix = substr(hash('sha256', $table), 0, 16);
                $triggerNames[] = 'audit_immut_'.$suffix.'_u';
                $triggerNames[] = 'audit_immut_'.$suffix.'_d';
            }
            $triggerNames[] = 'identity_decision_update_guard';
            $triggerNames[] = 'identity_decision_delete_guard';
            foreach ($triggerNames as $trigger) {
                $triggerExists->execute([$trigger]);
                $objects[] = 'trigger.'.$trigger.':'.(((int)$triggerExists->fetchColumn() === 1) ? 'PRESENT' : 'MISSING');
            }
        }

        $allPresent = true;
        foreach ($objects as $object) {
            if (str_ends_with($object, ':MISSING')) $allPresent = false;
        }
        $batchValuesValid = true;
        foreach ($batches as $batchValue) {
            if (! is_scalar($batchValue) || ! ctype_digit((string) $batchValue)) $batchValuesValid = false;
        }
        if ($duplicateCount > 0) {
            $status = 'DUPLICATE_LEDGER_ROWS';
            $migrationFailures++;
        } elseif ($recorded && ! $batchValuesValid) {
            $status = 'INVALID_LEDGER_BATCH_VALUE';
            $migrationFailures++;
        } elseif ($recorded && $allPresent) {
            $status = 'CONSISTENT';
        } elseif ($recorded) {
            $status = 'RECORDED_BUT_SCHEMA_INCOMPLETE';
            $migrationFailures++;
        } elseif ($allPresent) {
            $status = 'SCHEMA_PRESENT_BUT_NOT_RECORDED';
            $migrationFailures++;
        } else {
            $status = 'NOT_RECORDED_AND_SCHEMA_INCOMPLETE';
            $migrationFailures++;
        }
        $safeBatches = [];
        foreach ($batches as $batchValue) {
            $safeBatches[] = is_scalar($batchValue) && ctype_digit((string)$batchValue)
                ? (string)(int)$batchValue
                : '[INVALID]';
        }
        $batch = $recorded ? implode(',', $safeBatches) : 'NONE';
        echo "migration={$migration}|ledger=".($recorded ? 'RECORDED' : 'NOT_RECORDED')
            ."|ledger_rows=".count($batches)."|duplicate_rows={$duplicateCount}|batches={$batch}|status={$status}\n";
        foreach ($objects as $object) echo "object={$migration}|{$object}\n";
    }

    echo "reconciliation_summary=migrations_checked:".count($migrations)
        .";ledger_recorded:".count($ledgerRows)
        .";duplicate_ledger_rows:{$ledgerDuplicates}"
        .";migration_failures:{$migrationFailures}\n";
    $pdo->rollBack();
    $open = false;
    echo "read_only_transaction=ROLLED_BACK\n";
    echo "production_database_changed=false\n";
    echo "production_application_files_changed=false\n";
    echo "migrations_executed=false\n";
    echo "ddl_dml_executed=false\n";
    echo "persistent_report_written=false\n";
    echo "phase_1c_migration_ledger_reconciliation=COMPLETE\n";
} catch (Throwable $error) {
    if ($open && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, 'reconciliation_error='.get_class($error)."\n");
    fwrite(STDERR, "phase_1c_migration_ledger_reconciliation=FAIL\n");
    exit(21);
}

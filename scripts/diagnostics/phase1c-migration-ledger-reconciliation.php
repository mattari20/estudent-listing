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
    $parser = \Illuminate\\Database\\ConfigurationUrlParser::class;
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
    '2026_10_25_000001_create_structured_enrichment_promotion_events_table' => [
        'tables' => ['structured_enrichment_promotion_events'],
    ],
    '2026_11_03_000002_create_governed_execution_runs_table' => [
        'tables' => ['governed_execution_runs', 'governed_execution_run_events'],
    ],
    '2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency' => [
        'tables' => ['monitoring_alert_identities'],
        'columns' => ['security_audit_events.monitoring_idempotency_key'],
    ],
    '2026_11_04_000010_create_monitoring_policy_run_checkpoints' => [
        'tables' => ['monitoring_policy_runs', 'monitoring_policy_run_sources'],
    ],
    '2026_11_04_000012_add_identity_conflict_persistence_keys_and_events' => [
        'tables' => ['import_conflict_events'],
        'columns' => [
            'identity_match_candidates.identity_key',
            'identity_resolution_decisions.decision_identity_key',
            'import_conflicts.identity_key',
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
        $ledger[(string)$row['migration']] = (string)$row['batch'];
    }

    echo "phase_1c_migration_ledger_reconciliation=STARTED\n";
    echo "effective_database_config_source={$configSource}\n";
    echo "effective_database_connection_name={$name}\n";
    echo "effective_database_fingerprint={$fingerprint}\n";
    echo "ledger_rows_read=".count($ledgerRows)."\n";
    echo "application_rows_read_scope=selected_migrations_ledger_rows_only\n";

    $migrationFailures = 0;
    foreach ($migrations as $migration => $requirements) {
        $recorded = array_key_exists($migration, $ledger);
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
        if ($recorded && $allPresent) {
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
        $batch = $recorded ? $ledger[$migration] : 'NONE';
        echo "migration={$migration}|ledger=".($recorded ? 'RECORDED' : 'NOT_RECORDED')
            ."|batch={$batch}|status={$status}\n";
        foreach ($objects as $object) echo "object={$migration}|{$object}\n";
    }

    echo "reconciliation_summary=migrations_checked:".count($migrations)
        .";ledger_recorded:".count($ledgerRows)
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

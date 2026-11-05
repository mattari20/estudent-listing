<?php
declare(strict_types=1);

/**
 * Phase 1C Test 1B schema metadata inventory.
 * Reads information_schema only. No application-table rows, migrations,
 * DDL, DML, Artisan commands, or persistent output.
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
        'import_conflicts','identity_match_candidates',
    ];

    echo "phase_1c_test_1b_schema_inventory=STARTED\n";
    echo "effective_database_config_source={$configSource}\n";
    echo "effective_database_connection_name={$name}\n";
    echo "effective_database_fingerprint={$fingerprint}\n";
    echo "metadata_scope=information_schema_only\n";
    echo "application_table_rows_read=0\n";

    $tableStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?'
    );
    $columnStmt = $pdo->prepare(
        'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA, '.
        'CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE, DATETIME_PRECISION '.
        'FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? '.
        'ORDER BY ORDINAL_POSITION'
    );
    $indexStmt = $pdo->prepare(
        'SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, SUB_PART, INDEX_TYPE '.
        'FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? '.
        'ORDER BY INDEX_NAME, SEQ_IN_INDEX'
    );
    $fkStmt = $pdo->prepare(
        'SELECT k.CONSTRAINT_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, '.
        'k.REFERENCED_COLUMN_NAME, k.ORDINAL_POSITION, r.UPDATE_RULE, r.DELETE_RULE '.
        'FROM information_schema.KEY_COLUMN_USAGE k LEFT JOIN information_schema.REFERENTIAL_CONSTRAINTS r '.
        'ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME '.
        'AND r.TABLE_NAME=k.TABLE_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.TABLE_NAME=? '.
        'AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY k.CONSTRAINT_NAME, k.ORDINAL_POSITION'
    );

    $present = 0;
    $missing = 0;
    $columnTotal = 0;
    $indexTotal = 0;
    $foreignKeyTotal = 0;

    foreach ($tables as $table) {
        $tableStmt->execute([$table]);
        if ((int)$tableStmt->fetchColumn() !== 1) {
            $missing++;
            echo "table={$table}|status=MISSING\n";
            continue;
        }
        $present++;
        echo "table={$table}|status=PRESENT\n";

        $columnStmt->execute([$table]);
        $columns = $columnStmt->fetchAll();
        $columnTotal += count($columns);
        foreach ($columns as $c) {
            $parts = [
                $table, $c['COLUMN_NAME'], $c['COLUMN_TYPE'], $c['IS_NULLABLE'],
                $c['COLUMN_KEY'], $c['EXTRA'], $c['CHARACTER_MAXIMUM_LENGTH'],
                $c['NUMERIC_PRECISION'], $c['NUMERIC_SCALE'], $c['DATETIME_PRECISION'],
            ];
            echo 'column='.implode('|', array_map(
                static fn($v): string => str_replace(["\r", "\n", "|"], [' ', ' ', '/'], (string)($v ?? '')),
                $parts
            ))."\n";
        }

        $indexStmt->execute([$table]);
        $indexes = $indexStmt->fetchAll();
        $indexTotal += count($indexes);
        foreach ($indexes as $idx) {
            $parts = [
                $table, $idx['INDEX_NAME'], $idx['NON_UNIQUE'], $idx['SEQ_IN_INDEX'],
                $idx['COLUMN_NAME'], $idx['SUB_PART'], $idx['INDEX_TYPE'],
            ];
            echo 'index='.implode('|', array_map(
                static fn($v): string => str_replace(["\r", "\n", "|"], [' ', ' ', '/'], (string)($v ?? '')),
                $parts
            ))."\n";
        }

        $fkStmt->execute([$table]);
        $foreignKeys = $fkStmt->fetchAll();
        $foreignKeyTotal += count($foreignKeys);
        foreach ($foreignKeys as $fk) {
            $parts = [
                $table, $fk['CONSTRAINT_NAME'], $fk['COLUMN_NAME'],
                $fk['REFERENCED_TABLE_NAME'], $fk['REFERENCED_COLUMN_NAME'],
                $fk['ORDINAL_POSITION'], $fk['UPDATE_RULE'], $fk['DELETE_RULE'],
            ];
            echo 'foreign_key='.implode('|', array_map(
                static fn($v): string => str_replace(["\r", "\n", "|"], [' ', ' ', '/'], (string)($v ?? '')),
                $parts
            ))."\n";
        }
    }

    $triggerStmt = $pdo->query(
        'SELECT TRIGGER_NAME, EVENT_OBJECT_TABLE, ACTION_TIMING, EVENT_MANIPULATION, ACTION_STATEMENT '.
        'FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() ORDER BY TRIGGER_NAME'
    );
    $triggerRows = $triggerStmt->fetchAll();
    foreach ($triggerRows as $trigger) {
        echo 'trigger='.implode('|', [
            (string)$trigger['TRIGGER_NAME'],
            (string)$trigger['EVENT_OBJECT_TABLE'],
            (string)$trigger['ACTION_TIMING'],
            (string)$trigger['EVENT_MANIPULATION'],
            hash('sha256', (string)$trigger['ACTION_STATEMENT']),
        ])."\n";
    }

    echo 'inventory_summary=checked_tables:'.count($tables)
        .';present:'.$present
        .';missing:'.$missing
        .';columns:'.$columnTotal
        .';index_entries:'.$indexTotal
        .';foreign_key_entries:'.$foreignKeyTotal
        .';triggers:'.count($triggerRows)."\n";

    $pdo->rollBack();
    $open = false;
    echo "read_only_transaction=ROLLED_BACK\n";
    echo "production_database_changed=false\n";
    echo "production_application_files_changed=false\n";
    echo "persistent_report_written=false\n";
    echo "phase_1c_test_1b_schema_inventory=COMPLETE\n";
} catch (Throwable $error) {
    if ($open && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, 'schema_inventory_error='.get_class($error)."\n");
    fwrite(STDERR, "phase_1c_test_1b_schema_inventory=FAIL\n");
    exit(21);
}

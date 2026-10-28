<?php
$diagnosticStage = 'php_program_started';
register_shutdown_function(static function () use (&$diagnosticStage): void {
    $lastError = error_get_last();
    if (! is_array($lastError)) {
        return;
    }
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (in_array($lastError['type'] ?? null, $fatalTypes, true)) {
        fwrite(STDERR, 'php_shutdown_fatal=true stage='.$diagnosticStage
            .' type='.(int) $lastError['type']
            .' line='.(int) ($lastError['line'] ?? 0).PHP_EOL);
    }
});
fwrite(STDERR, "diagnostic_checkpoint=php_program_started".PHP_EOL);
$diagnosticStage = 'composer_autoload';
fwrite(STDERR, "diagnostic_checkpoint=composer_autoload".PHP_EOL);
$projectPath = getenv('PROJECT_PATH');
if (! is_string($projectPath) || $projectPath === '') {
    fwrite(STDERR, "ERROR: project path unavailable to PHP diagnostic.".PHP_EOL);
    exit(32);
}
require rtrim($projectPath, '/').'/vendor/autoload.php';
$diagnosticStage = 'laravel_bootstrap';
fwrite(STDERR, "diagnostic_checkpoint=laravel_bootstrap".PHP_EOL);
$app = require rtrim($projectPath, '/').'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
fwrite(STDERR, "diagnostic_checkpoint=laravel_bootstrap_complete".PHP_EOL);
$diagnosticStage = 'schema_preflight';

$schema = \Illuminate\Support\Facades\Schema::class;
$db = \Illuminate\Support\Facades\DB::class;
$artisan = \Illuminate\Support\Facades\Artisan::class;
$table = 'official_source_refresh_events';
$migration = '2026_10_25_000002_create_official_source_refresh_events_table';
$path = 'database/migrations/'.$migration.'.php';
$requiredColumns = [
    'id', 'source_id', 'source_change_observation_id', 'canonical_type',
    'canonical_id', 'field_name', 'before_value', 'after_value',
    'evidence_url', 'evidence_excerpt', 'content_fingerprint',
    'observed_at', 'occurred_at', 'created_at', 'updated_at',
];

if (! is_file(base_path($path))) {
    fwrite(STDERR, "ERROR: required migration file is missing.".PHP_EOL);
    exit(20);
}
$diagnosticStage = 'migrations_table_check';
fwrite(STDERR, "diagnostic_checkpoint=migrations_table_check".PHP_EOL);
if (! $schema::hasTable('migrations')) {
    fwrite(STDERR, "ERROR: migrations table is missing; manual schema review is required.".PHP_EOL);
    exit(21);
}

if (getenv('CONFIRM_INDEX_REPAIR') === 'true' && getenv('CONFIRM_MIGRATION') === 'true') {
    fwrite(STDERR, "ERROR: index repair and migration-ledger reconciliation are separate operations; select only one confirmation.".PHP_EOL);
    exit(19);
}

$requiredIndexes = [
    'official_refresh_source_idx' => ['source_id'],
    'official_refresh_observation_idx' => ['source_change_observation_id'],
    'official_refresh_canonical_field_idx' => ['canonical_type', 'canonical_id', 'field_name'],
];
// Treat an equivalent non-unique index with identical ordered columns as satisfying the logical contract.
// Preserve its existing name; never rename or create a duplicate index.
$indexSatisfaction = static function (array $allIndexes) use ($requiredIndexes): array {
    $existing = [];
    foreach ($allIndexes as $index) {
        $name = $index['name'] ?? null;
        if (is_string($name) && $name !== '') {
            $existing[$name] = [
                'columns' => array_values($index['columns'] ?? []),
                'unique' => (bool) ($index['unique'] ?? false),
            ];
        }
    }

    $missing = [];
    $satisfiedBy = [];
    foreach ($requiredIndexes as $name => $columns) {
        $actual = $existing[$name] ?? null;
        if ($actual !== null) {
            if ($actual['columns'] === $columns && $actual['unique'] === false) {
                $satisfiedBy[$name] = $name;
                continue;
            }
            $missing[$name] = $columns;
            continue;
        }

        foreach ($existing as $existingName => $definition) {
            if ($definition['columns'] === $columns && $definition['unique'] === false) {
                $satisfiedBy[$name] = $existingName;
                continue 2;
            }
        }
        $missing[$name] = $columns;
    }

    return [$missing, $satisfiedBy, $existing];
};
$diagnosticStage = 'target_table_check';
fwrite(STDERR, "diagnostic_checkpoint=target_table_check".PHP_EOL);
$tableExists = $schema::hasTable($table);
$diagnosticStage = 'migration_ledger_lookup';
fwrite(STDERR, "diagnostic_checkpoint=migration_ledger_lookup".PHP_EOL);
$migrationApplied = $db::table('migrations')->where('migration', $migration)->exists();
$missingColumns = [];
$missingIndexes = [];
$inspectSchema = static function () use ($schema, $table, $requiredColumns, $indexSatisfaction, &$diagnosticStage): array {
    $diagnosticStage = 'required_column_introspection';
    fwrite(STDERR, "diagnostic_checkpoint=required_column_introspection".PHP_EOL);
    $missingColumns = [];
    foreach ($requiredColumns as $column) {
        if (! $schema::hasColumn($table, $column)) {
            $missingColumns[] = $column;
        }
    }

    $diagnosticStage = 'required_index_introspection';
    fwrite(STDERR, "diagnostic_checkpoint=required_index_introspection".PHP_EOL);
    [$missingIndexes] = $indexSatisfaction($schema::getIndexes($table));

    return [$missingColumns, $missingIndexes];
};

if ($tableExists) {
    [$missingColumns, $missingIndexes] = $inspectSchema();
}
$schemaMatches = $tableExists && $missingColumns === [] && $missingIndexes === [];

// Explicit, separate, additive index repair. Never infer approval from
// confirm_migration; the latter is reserved for ledger-only reconciliation.
if (getenv('CONFIRM_INDEX_REPAIR') === 'true') {
    $diagnosticStage = 'index_repair_preconditions';
    fwrite(STDERR, "diagnostic_checkpoint=index_repair_preconditions".PHP_EOL);
    if (! $tableExists || $missingColumns !== []) {
        fwrite(STDERR, json_encode([
            'migration_status' => 'BLOCKED_INDEX_REPAIR_SCHEMA_COLUMNS_NOT_READY',
            'table_exists' => $tableExists,
            'missing_columns' => $missingColumns,
            'migration_executed_this_run' => false,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
        exit(25);
    }

    $diagnosticStage = 'index_repair_index_inventory';
    fwrite(STDERR, "diagnostic_checkpoint=index_repair_index_inventory".PHP_EOL);
    $allIndexes = $schema::getIndexes($table);
    [$missing, $satisfiedBy, $existingByName] = $indexSatisfaction($allIndexes);
    foreach ($requiredIndexes as $name => $columns) {
        if (isset($existingByName[$name]) && ! isset($satisfiedBy[$name])) {
            fwrite(STDERR, json_encode([
                'migration_status' => 'BLOCKED_INDEX_NAME_COLLISION',
                'index' => $name,
                'actual' => $existingByName[$name],
                'expected' => ['columns' => $columns, 'unique' => false],
                'production_schema_mutations' => 'NONE',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
            exit(26);
        }
    }
    $repairable = $missing;

    if ($repairable === []) {
        echo json_encode([
            'migration_status' => 'INDEX_REPAIR_NOT_NEEDED',
            'missing_indexes' => [],
            'migration_ledger_changed' => false,
            'production_data_mutations' => 'NONE',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
        exit(0);
    }

    $applied = [];
    foreach ($repairable as $name => $columns) {
        $diagnosticStage = 'index_repair_mutation';
        fwrite(STDERR, "diagnostic_checkpoint=index_repair_mutation".PHP_EOL);
        $schema::table($table, static function (\Illuminate\Database\Schema\Blueprint $blueprint) use ($name, $columns): void {
            $blueprint->index($columns, $name);
        });
        $applied[] = $name;
    }

    [$missingColumnsAfter, $missingIndexesAfter] = $inspectSchema();
    if ($missingColumnsAfter !== [] || $missingIndexesAfter !== []) {
        fwrite(STDERR, json_encode([
            'migration_status' => 'INDEX_REPAIR_POSTCHECK_FAILED',
            'indexes_added' => $applied,
            'missing_columns' => $missingColumnsAfter,
            'missing_indexes' => $missingIndexesAfter,
            'migration_ledger_changed' => false,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
        exit(28);
    }

    echo json_encode([
        'migration_status' => 'INDEX_REPAIR_APPLIED_AND_VERIFIED',
        'indexes_added' => $applied,
        'indexes_satisfied_by_existing_equivalent' => $satisfiedBy,
        'migration_ledger_changed' => false,
        'production_canonical_records_changed' => 'NO',
        'next_action' => 'rerun_readiness_read_only_then_reconcile_ledger_only_if_schema_is_fully_ready',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
    exit(0);
}

if ($schemaMatches && $migrationApplied) {
    echo json_encode([
        'migration_status' => 'ALREADY_READY',
        'table_exists' => true,
        'migration_applied' => true,
        'missing_columns' => [],
        'missing_indexes' => [],
        'migration_executed_this_run' => false,
        'production_data_mutations' => 'NONE',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
    exit(0);
}

// Existing-but-untracked schema is recoverable only when the physical
// columns and named indexes exactly match the migration. The migration's
// idempotent up() verifies that same contract and makes no schema changes;
// Laravel records the missing migration ledger row only after it returns.
if (($tableExists && ! $schemaMatches) || ($migrationApplied && ! $tableExists)) {
    fwrite(STDERR, json_encode([
        'migration_status' => 'BLOCKED_INCONSISTENT_SCHEMA',
        'table_exists' => $tableExists,
        'migration_applied' => $migrationApplied,
        'missing_columns' => $missingColumns,
        'missing_indexes' => $missingIndexes,
        'migration_executed_this_run' => false,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    exit(22);
}

if ($schemaMatches && ! $migrationApplied && getenv('CONFIRM_MIGRATION') !== 'true') {
    echo json_encode([
        'migration_status' => 'SCHEMA_PRESENT_LEDGER_RECONCILIATION_AVAILABLE',
        'table_exists' => true,
        'migration_applied' => false,
        'missing_columns' => [],
        'missing_indexes' => [],
        'migration_would_run' => true,
        'migration_executed_this_run' => false,
        'production_data_mutations' => 'NONE',
        'next_action' => 'rerun_with_confirm_migration_true_after reviewing verified schema and missing ledger row',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
    exit(0);
}

if (getenv('CONFIRM_MIGRATION') !== 'true') {
    echo json_encode([
        'migration_status' => 'NOT_APPLIED_DRY_RUN',
        'table_exists' => false,
        'migration_applied' => false,
        'required_columns' => $requiredColumns,
        'migration_would_run' => true,
        'migration_executed_this_run' => false,
        'production_data_mutations' => 'NONE',
        'next_action' => 'rerun_with_confirm_migration_true_after reviewing this report',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
    exit(0);
}

$diagnosticStage = 'migration_command';
fwrite(STDERR, "diagnostic_checkpoint=migration_command".PHP_EOL);
try {
    $exitCode = $artisan::call('migrate', [
        '--force' => true,
        '--path' => $path,
    ]);
    echo $artisan::output();
} catch (\Throwable $exception) {
    // Never emit exception messages: they may contain SQL or connection details.
    fwrite(STDERR, "ERROR: targeted migration threw an exception (details suppressed).".PHP_EOL);
    exit(23);
}
if ($exitCode !== 0) {
    fwrite(STDERR, "ERROR: targeted migration command failed with exit code ".$exitCode.".".PHP_EOL);
    exit(23);
}

$diagnosticStage = 'post_migration_table_verification';
fwrite(STDERR, "diagnostic_checkpoint=post_migration_table_verification".PHP_EOL);
$tableExists = $schema::hasTable($table);
$diagnosticStage = 'post_migration_ledger_verification';
fwrite(STDERR, "diagnostic_checkpoint=post_migration_ledger_verification".PHP_EOL);
$migrationApplied = $db::table('migrations')->where('migration', $migration)->exists();
$missingColumns = [];
$missingIndexes = [];
if ($tableExists) {
    [$missingColumns, $missingIndexes] = $inspectSchema();
}
$ready = $tableExists && $migrationApplied && $missingColumns === [] && $missingIndexes === [];
echo json_encode([
    'migration_status' => $ready ? 'APPLIED_AND_VERIFIED' : 'POST_MIGRATION_VERIFICATION_FAILED',
    'table_exists' => $tableExists,
    'migration_applied' => $migrationApplied,
    'missing_columns' => $missingColumns,
    'missing_indexes' => $missingIndexes,
    'migration_executed_this_run' => true,
    'production_data_mutations' => 'NO_CANONICAL_RECORDS_CHANGED',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
if (! $ready) {
    exit(24);
}

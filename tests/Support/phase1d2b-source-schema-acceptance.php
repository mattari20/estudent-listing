<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2).'/scripts/phase1c/readonly-schema-diagnostic.php';

function phase1d2bAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('phase1d2b_source_acceptance_failed: '.$message);
    }
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_DATABASE') ?: 'phase1d2b_schema';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'root';

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

$result = (new Phase1CReadOnlyDiagnostic($pdo))->collect($database);
$schema = $result['source_schema_reconciliation'] ?? [];
phase1d2bAssert(($schema['table_count'] ?? null) === 63, 'source comparator must cover all 63 scope entries');
phase1d2bAssert(($schema['complete_table_count'] ?? null) === 63, 'all 63 application and framework table definitions must compare successfully');
phase1d2bAssert(($schema['schema_equivalence_proven'] ?? false) === true && ($schema['state'] ?? null) === 'PASS', 'schema equivalence requires every source-defined object and table to compare successfully');
phase1d2bAssert(count($schema['results'] ?? []) === 63, 'all per-table results must be present');
phase1d2bAssert(($schema['unexpected_tables'] ?? []) === [], 'fresh migration schema must contain no unexpected tables');

$counts = ['columns'=>0,'indexes'=>0,'foreign_keys'=>0,'triggers'=>0,'table_options_pass'=>0,'table_options_not_checked'=>0];
$nonPass = [];
foreach ($schema['results'] as $table => $tableResult) {
    phase1d2bAssert(($tableResult['objects']['table']['state'] ?? null) === 'PASS', $table.' table definition and inherited engine/options must compare successfully');
    $optionState = $tableResult['objects']['table_options']['state'] ?? 'UNKNOWN';
    if ($optionState === 'PASS') $counts['table_options_pass']++;
    elseif ($optionState === 'NOT_CHECKED') $counts['table_options_not_checked']++;
    else $nonPass[] = ['table'=>$table,'kind'=>'table_options','state'=>$optionState];

    foreach (['columns','indexes','foreign_keys','triggers'] as $kind) {
        foreach (($tableResult['objects'][$kind] ?? []) as $object) {
            $counts[$kind]++;
            if (($object['state'] ?? null) !== 'PASS') {
                $nonPass[] = ['table'=>$table,'kind'=>$kind,'object'=>$object['object'] ?? 'unknown','state'=>$object['state'] ?? 'UNKNOWN','reason'=>$object['reason'] ?? null,'mismatch_fields'=>$object['mismatch_fields'] ?? [],'expected'=>$object['expected'] ?? null,'observed'=>$object['observed'] ?? null];
            }
        }
    }
}
phase1d2bAssert($counts['columns'] === 796, 'expected 796 columns; observed '.$counts['columns'].'; mismatches='.json_encode(array_slice($nonPass, 0, 8), JSON_THROW_ON_ERROR));
phase1d2bAssert($counts['indexes'] === 252, 'expected 252 indexes; observed '.$counts['indexes'].'; index mismatches='.json_encode(array_slice(array_values(array_filter($nonPass, static fn(array $x): bool => ($x['kind'] ?? '') === 'indexes')), 0, 20), JSON_THROW_ON_ERROR));
phase1d2bAssert($counts['foreign_keys'] === 89, 'expected 89 foreign keys; observed '.$counts['foreign_keys'].'; mismatches='.json_encode(array_slice($nonPass, 0, 8), JSON_THROW_ON_ERROR));
phase1d2bAssert($counts['triggers'] === 24, 'expected 24 triggers; observed '.$counts['triggers'].'; mismatches='.json_encode(array_slice($nonPass, 0, 8), JSON_THROW_ON_ERROR));
phase1d2bAssert($counts['table_options_pass'] === 63, 'charset/collation/server-default options must reconcile for all 62 application tables and the framework ledger');
phase1d2bAssert($counts['table_options_not_checked'] === 0, 'no table options remain NOT_CHECKED after inherited connection defaults are resolved');
phase1d2bAssert($nonPass === [], 'all source object contracts must match the clean migration result: '.json_encode(array_slice($nonPass, 0, 20), JSON_THROW_ON_ERROR));

$ledger = $result['full_migration_ledger_inventory'] ?? [];
phase1d2bAssert(($ledger['state'] ?? null) === 'PASS', 'all 97 migration source files must be represented in the fresh Laravel ledger');
phase1d2bAssert(($ledger['source_file_count'] ?? null) === 97, 'source migration count must be 97');
phase1d2bAssert(($ledger['ledger_row_count'] ?? null) === 97, 'fresh ledger must contain 97 rows');

$full = $result['full_source_migration_reconciliation'] ?? [];
phase1d2bAssert(count($full['migration_object_reconciliation'] ?? []) === 97, 'all 97 migrations must have object reconciliation rows');
$objectStates = $full['object_state_counts'] ?? [];
phase1d2bAssert(($objectStates['PASS'] ?? 0) === 97, 'all 97 reviewed migrations must reconcile to object PASS');
phase1d2bAssert(($objectStates['NOT_CHECKED'] ?? 0) === 0, 'no migration object mapping may remain NOT_CHECKED on a clean fully migrated schema');

echo json_encode([
    'engine'=>$result['database']['engine'] ?? 'UNKNOWN',
    'server_version'=>$result['database']['server_version'] ?? 'UNKNOWN',
    'source_tables'=>63,
    'complete_table_definitions'=>62,
    'complete_framework_ledger_definition'=>1,
    'partial_table_definitions'=>0,
    'object_contract_counts'=>$counts,
    'migration_ledger_rows'=>$ledger['ledger_row_count'] ?? null,
    'migration_object_states'=>$objectStates,
    'schema_equivalence_proven'=>true,
    'result'=>'PASS_COMPLETE_SCHEMA_EQUIVALENCE',
], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;

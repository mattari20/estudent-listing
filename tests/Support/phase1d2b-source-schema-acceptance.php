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
phase1d2bAssert(($schema['complete_table_count'] ?? null) === 0, 'partial source definitions must not be promoted to complete');
phase1d2bAssert(($schema['schema_equivalence_proven'] ?? true) === false, 'conditional source definitions must not claim complete equivalence');
phase1d2bAssert(count($schema['results'] ?? []) === 63, 'all per-table results must be present');
phase1d2bAssert(($schema['unexpected_tables'] ?? []) === [], 'fresh migration schema must contain no unexpected tables');

$counts = ['columns'=>0,'indexes'=>0,'foreign_keys'=>0,'triggers'=>0,'table_options_pass'=>0,'table_options_not_checked'=>0];
$nonPass = [];
foreach ($schema['results'] as $table => $tableResult) {
    phase1d2bAssert(($tableResult['objects']['table']['state'] ?? null) === 'NOT_CHECKED', $table.' table-level contract must remain NOT_CHECKED while source table definition is partial');
    $optionState = $tableResult['objects']['table_options']['state'] ?? 'UNKNOWN';
    if ($optionState === 'PASS') $counts['table_options_pass']++;
    elseif ($optionState === 'NOT_CHECKED') $counts['table_options_not_checked']++;
    else $nonPass[] = ['table'=>$table,'kind'=>'table_options','state'=>$optionState];

    foreach (['columns','indexes','foreign_keys','triggers'] as $kind) {
        foreach (($tableResult['objects'][$kind] ?? []) as $object) {
            $counts[$kind]++;
            if (($object['state'] ?? null) !== 'PASS') {
                $nonPass[] = ['table'=>$table,'kind'=>$kind,'object'=>$object['object'] ?? 'unknown','state'=>$object['state'] ?? 'UNKNOWN','reason'=>$object['reason'] ?? null];
            }
        }
    }
}
phase1d2bAssert($counts['columns'] === 796, 'all 796 source-derived column contracts including the 3-column framework ledger must be compared');
phase1d2bAssert($counts['indexes'] === 232, 'all 232 source-derived indexes including the framework ledger primary key must be compared');
phase1d2bAssert($counts['foreign_keys'] === 89, 'all 89 source-derived foreign-key contracts must be compared');
phase1d2bAssert($counts['triggers'] === 24, 'all 24 source-derived trigger contracts must be compared');
phase1d2bAssert($counts['table_options_pass'] === 62, 'charset/collation/server-default options must reconcile for all application tables');
phase1d2bAssert($counts['table_options_not_checked'] === 1, 'the framework ledger retains its separately inherited connection options');
phase1d2bAssert($nonPass === [], 'all source object contracts must match the clean migration result: '.json_encode(array_slice($nonPass, 0, 20), JSON_THROW_ON_ERROR));

$ledger = $result['full_migration_ledger_inventory'] ?? [];
phase1d2bAssert(($ledger['state'] ?? null) === 'PASS', 'all 97 migration source files must be represented in the fresh Laravel ledger');
phase1d2bAssert(($ledger['source_file_count'] ?? null) === 97, 'source migration count must be 97');
phase1d2bAssert(($ledger['ledger_row_count'] ?? null) === 97, 'fresh ledger must contain 97 rows');

$full = $result['full_source_migration_reconciliation'] ?? [];
phase1d2bAssert(count($full['migration_object_reconciliation'] ?? []) === 97, 'all 97 migrations must have object reconciliation rows');
$objectStates = $full['object_state_counts'] ?? [];
phase1d2bAssert(($objectStates['PASS'] ?? 0) === 23, 'the 23 reviewed no-op migrations must remain object-neutral');
phase1d2bAssert(($objectStates['NOT_CHECKED'] ?? 0) === 74, 'the 74 partially reviewed migrations must remain NOT_CHECKED pending final cumulative acceptance');

echo json_encode([
    'engine'=>$result['database']['engine'] ?? 'UNKNOWN',
    'server_version'=>$result['database']['server_version'] ?? 'UNKNOWN',
    'source_tables'=>63,
    'complete_table_definitions'=>0,
    'partial_table_definitions'=>62,
    'object_contract_counts'=>$counts,
    'migration_ledger_rows'=>$ledger['ledger_row_count'] ?? null,
    'migration_object_states'=>$objectStates,
    'schema_equivalence_proven'=>false,
    'result'=>'PASS_SOURCE_OBJECT_RECONCILIATION_WITH_TABLE_DEFINITIONS_STILL_PARTIAL',
], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;

<?php
declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function bad(string $message): never
{
    fwrite(STDERR, "FAILED: {$message}\n");
    exit(1);
}

$table = 'institution_field_approvals';

if (!Schema::hasTable($table)) {
    bad('table missing');
}

$expectedColumns = [
    'id' => ['bigint', false],
    'institution_id' => ['bigint', false],
    'field_name' => ['varchar', false],
    'expected_current_value' => ['longtext', true],
    'proposed_value' => ['longtext', false],
    'source_id' => ['bigint', false],
    'source_change_observation_id' => ['bigint', false],
    'source_snapshot_id' => ['bigint', false],
    'evidence_id' => ['bigint', false],
    'evidence_url' => ['text', false],
    'evidence_excerpt' => ['longtext', false],
    'content_fingerprint' => ['varchar', false],
    'source_observed_at' => ['varchar', false],
    'evidence_observed_at' => ['varchar', true],
    'proposal_hash' => ['char', false],
    'status' => ['varchar', false],
    'created_by' => ['bigint', false],
    'reviewer_id' => ['bigint', true],
    'decision_at' => ['timestamp', true],
    'decision_reason' => ['text', true],
    'applied_at' => ['timestamp', true],
    'application_outcome' => ['varchar', true],
    'applied_refresh_event_id' => ['bigint', true],
    'created_at' => ['timestamp', true],
    'updated_at' => ['timestamp', true],
];

$columns = collect(Schema::getColumns($table))->keyBy('name');

if ($columns->count() !== count($expectedColumns)) {
    bad('expected '.count($expectedColumns).' columns, found '.$columns->count());
}

foreach ($expectedColumns as $name => [$type, $nullable]) {
    if (!$columns->has($name)) {
        bad("missing column {$name}");
    }

    $column = $columns->get($name);
    $actualType = strtolower((string) ($column['type_name'] ?? $column['type'] ?? ''));

    if (!str_contains($actualType, $type)) {
        bad("wrong type for {$name}: {$actualType}");
    }

    if (($column['nullable'] ?? null) !== $nullable) {
        bad("wrong nullability for {$name}");
    }
}

if (($columns->get('status')['default'] ?? null) !== 'pending') {
    bad('status default not pending');
}

if (!($columns->get('id')['auto_increment'] ?? false)) {
    bad('id is not auto increment');
}

$indexes = collect(Schema::getIndexes($table))->keyBy('name');

$expectedIndexes = [
    'PRIMARY' => [['id'], true, true],
    'institution_field_approvals_proposal_hash_unique' => [['proposal_hash'], true, false],
    'inst_field_approval_state_idx' => [['institution_id', 'field_name', 'status'], false, false],
    'inst_field_approval_source_idx' => [['source_id', 'source_change_observation_id'], false, false],
    'inst_field_approval_decision_idx' => [['status', 'decision_at'], false, false],
];

foreach ($expectedIndexes as $name => [$expectedIndexColumns, $unique, $primary]) {
    $index = $indexes->get($name);

    if (!is_array($index)) {
        bad("missing index {$name}");
    }

    if (
        array_values($index['columns'] ?? []) !== $expectedIndexColumns
        || (bool) ($index['unique'] ?? false) !== $unique
        || (bool) ($index['primary'] ?? false) !== $primary
    ) {
        bad("index mismatch {$name}");
    }
}

$foreignKeys = collect(Schema::getForeignKeys($table));

$expectedForeignKeys = [
    'institution_id' => ['institutions', 'id'],
    'source_id' => ['sources', 'id'],
    'source_change_observation_id' => ['source_change_observations', 'id'],
    'source_snapshot_id' => ['source_snapshots', 'id'],
    'evidence_id' => ['evidence', 'id'],
    'created_by' => ['users', 'id'],
    'reviewer_id' => ['users', 'id'],
    'applied_refresh_event_id' => ['official_source_refresh_events', 'id'],
];

foreach ($expectedForeignKeys as $column => [$foreignTable, $foreignColumn]) {
    $matches = $foreignKeys->filter(
        fn (array $foreignKey): bool =>
            array_values($foreignKey['columns'] ?? []) === [$column]
            && ($foreignKey['foreign_table'] ?? null) === $foreignTable
            && array_values($foreignKey['foreign_columns'] ?? []) === [$foreignColumn]
    );

    if ($matches->count() !== 1) {
        bad("missing/duplicate FK {$column} -> {$foreignTable}.{$foreignColumn}");
    }

    if (!in_array(strtolower((string) ($matches->first()['on_delete'] ?? '')), ['restrict', 'no action'], true)) {
        bad("non-restrictive delete for {$column}");
    }
}

if ($foreignKeys->count() !== count($expectedForeignKeys)) {
    bad('unexpected foreign key count');
}

$migration = '2026_10_29_000001_create_institution_field_approvals_table';

if (DB::table('migrations')->where('migration', $migration)->count() !== 1) {
    bad('migration ledger count is not one');
}

echo 'Verified '.count($expectedColumns).' columns, defaults, five expected indexes, eight restrictive foreign keys, and one ledger entry.'.PHP_EOL;

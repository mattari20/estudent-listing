<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (DB::getDriverName() !== 'mysql') {
    fwrite(STDERR, "This schema-object acceptance proof requires isolated MySQL.\n");
    exit(2);
}

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$requiredTables = [
    'password_reset_tokens',
    'structured_enrichment_promotion_events',
    'governed_execution_leases',
    'governed_execution_runs',
    'governed_execution_run_events',
    'data_reverification_queue_identities',
    'monitoring_alert_identities',
    'monitoring_policy_runs',
    'monitoring_policy_run_sources',
    'import_conflict_events',
    'source_snapshot_reconciliation_requests',
];

foreach ($requiredTables as $table) {
    $assert(Schema::hasTable($table), 'missing table '.$table);
}

$requiredColumns = [
    'users' => ['must_change_password'],
    'import_batches' => ['duplicate_rows', 'discovery_identity_key'],
    'source_retrieval_runs' => ['idempotency_key'],
    'source_snapshots' => ['capture_key'],
    'source_change_observations' => ['current_snapshot_id'],
    'institute_discovery_run_outcomes' => ['idempotency_key'],
    'change_events' => ['source_change_assertion_link_id'],
    'data_reverification_queue_identities' => ['identity_key', 'subject_type', 'subject_id', 'attribute', 'active_queue_id'],
    'monitoring_alert_identities' => ['dedupe_key', 'active_alert_id'],
    'security_audit_events' => ['monitoring_idempotency_key'],
    'monitoring_policy_runs' => ['heartbeat_at', 'source_monitoring_policy_id', 'execution_run_id'],
    'monitoring_policy_run_sources' => ['monitoring_policy_run_id', 'source_id', 'retrieval_run_id', 'observation_id'],
    'evidence' => ['discovery_identity_key'],
    'import_rows' => ['discovery_identity_key'],
    'assertions' => ['discovery_identity_key'],
    'identity_match_candidates' => ['identity_key'],
    'identity_resolution_decisions' => ['decision_identity_key'],
    'import_conflicts' => ['identity_key'],
    'import_conflict_events' => ['idempotency_key'],
    'source_snapshot_reconciliation_requests' => ['identity_key', 'source_id', 'triggering_snapshot_id', 'current_snapshot_id'],
];

foreach ($requiredColumns as $table => $columns) {
    foreach ($columns as $column) {
        $assert(Schema::hasColumn($table, $column), 'missing column '.$table.'.'.$column);
    }
}

$requiredIndexes = [
    ['governed_execution_leases', 'governed_execution_leases_task_key_unique'],
    ['governed_execution_leases', 'governed_execution_leases_expires_at_index'],
    ['source_retrieval_runs', 'source_retrieval_runs_idempotency_key_unique'],
    ['source_snapshots', 'source_snapshots_capture_key_unique'],
    ['source_change_observations', 'source_observations_current_snapshot_unique'],
    ['institute_discovery_run_outcomes', 'institute_discovery_run_outcomes_idempotency_key_unique'],
    ['change_events', 'change_events_source_link_unique'],
    ['monitoring_policy_run_sources', 'monitoring_policy_run_source_unique'],
    ['import_batches', 'import_batches_discovery_identity_unique'],
    ['evidence', 'evidence_discovery_identity_unique'],
    ['import_rows', 'import_rows_discovery_identity_unique'],
    ['assertions', 'assertions_discovery_identity_unique'],
    ['identity_match_candidates', 'identity_match_candidates_identity_key_unique'],
    ['identity_resolution_decisions', 'identity_resolution_decisions_identity_key_unique'],
    ['import_conflicts', 'import_conflicts_identity_key_unique'],
    ['import_conflict_events', 'import_conflict_events_idempotency_key_unique'],
    ['source_snapshot_reconciliation_requests', 'source_snapshot_reconciliation_requests_identity_key_unique'],
    ['source_snapshot_reconciliation_requests', 'snapshot_reconciliation_source_status_idx'],
    ['source_snapshot_reconciliation_requests', 'snapshot_reconciliation_current_status_idx'],
];

foreach ($requiredIndexes as [$table, $index]) {
    $assert(Schema::hasIndex($table, $index), 'missing index '.$table.'.'.$index);
}

$expectedForeignKeys = [
    'identity_decision_row_restrict_fk' => ['identity_resolution_decisions', 'import_row_id', 'import_rows', 'id', 'RESTRICT'],
    'verification_decision_assertion_restrict_fk' => ['verification_decisions', 'assertion_id', 'assertions', 'id', 'RESTRICT'],
    'import_reconciliation_batch_restrict_fk' => ['import_reconciliation_events', 'import_batch_id', 'import_batches', 'id', 'RESTRICT'],
    'import_promotion_decision_row_restrict_fk' => ['import_promotion_decisions', 'import_row_id', 'import_rows', 'id', 'RESTRICT'],
    'import_promotion_event_row_restrict_fk' => ['import_promotion_events', 'import_row_id', 'import_rows', 'id', 'RESTRICT'],
    'import_promotion_event_batch_restrict_fk' => ['import_promotion_events', 'import_batch_id', 'import_batches', 'id', 'RESTRICT'],
    'reverification_event_queue_restrict_fk' => ['reverification_events', 'queue_id', 'data_reverification_queue', 'id', 'RESTRICT'],
    'structured_enrichment_row_restrict_fk' => ['structured_enrichment_promotion_events', 'enrichment_import_row_id', 'enrichment_import_rows', 'id', 'RESTRICT'],
    'structured_enrichment_batch_restrict_fk' => ['structured_enrichment_promotion_events', 'import_batch_id', 'import_batches', 'id', 'RESTRICT'],
    'import_conflict_event_restrict_fk' => ['import_conflict_events', 'import_conflict_id', 'import_conflicts', 'id', 'RESTRICT'],
    'governed_run_event_restrict_fk' => ['governed_execution_run_events', 'run_id', 'governed_execution_runs', 'id', 'RESTRICT'],
    'ssr_source_fk' => ['source_snapshot_reconciliation_requests', 'source_id', 'sources', 'id', 'CASCADE'],
    'ssr_triggering_snapshot_fk' => ['source_snapshot_reconciliation_requests', 'triggering_snapshot_id', 'source_snapshots', 'id', 'CASCADE'],
    'ssr_current_snapshot_fk' => ['source_snapshot_reconciliation_requests', 'current_snapshot_id', 'source_snapshots', 'id', 'CASCADE'],
    'ssr_expected_previous_fk' => ['source_snapshot_reconciliation_requests', 'expected_previous_snapshot_id', 'source_snapshots', 'id', 'SET NULL'],
    'ssr_recorded_previous_fk' => ['source_snapshot_reconciliation_requests', 'recorded_previous_snapshot_id', 'source_snapshots', 'id', 'SET NULL'],
    'mp_run_policy_fk' => ['monitoring_policy_runs', 'source_monitoring_policy_id', 'source_monitoring_policies', 'id', 'RESTRICT'],
    'mp_run_exec_fk' => ['monitoring_policy_runs', 'execution_run_id', 'governed_execution_runs', 'id', 'SET NULL'],
    'mp_source_run_fk' => ['monitoring_policy_run_sources', 'monitoring_policy_run_id', 'monitoring_policy_runs', 'id', 'RESTRICT'],
    'mp_source_source_fk' => ['monitoring_policy_run_sources', 'source_id', 'sources', 'id', 'RESTRICT'],
    'mp_source_retrieval_fk' => ['monitoring_policy_run_sources', 'retrieval_run_id', 'source_retrieval_runs', 'id', 'SET NULL'],
    'mp_source_observation_fk' => ['monitoring_policy_run_sources', 'observation_id', 'source_change_observations', 'id', 'SET NULL'],
    'mai_active_alert_fk' => ['monitoring_alert_identities', 'active_alert_id', 'monitoring_alerts', 'id', 'SET NULL'],
];

foreach ($expectedForeignKeys as $name => [$table, $column, $parent, $parentColumn, $deleteRule]) {
    $rows = DB::table('information_schema.KEY_COLUMN_USAGE')
        ->join('information_schema.REFERENTIAL_CONSTRAINTS', function ($join): void {
            $join->on('REFERENTIAL_CONSTRAINTS.CONSTRAINT_SCHEMA', '=', 'KEY_COLUMN_USAGE.CONSTRAINT_SCHEMA')
                ->on('REFERENTIAL_CONSTRAINTS.CONSTRAINT_NAME', '=', 'KEY_COLUMN_USAGE.CONSTRAINT_NAME')
                ->on('REFERENTIAL_CONSTRAINTS.TABLE_NAME', '=', 'KEY_COLUMN_USAGE.TABLE_NAME');
        })
        ->select([
            'KEY_COLUMN_USAGE.TABLE_NAME',
            'KEY_COLUMN_USAGE.COLUMN_NAME',
            'KEY_COLUMN_USAGE.REFERENCED_TABLE_SCHEMA',
            'KEY_COLUMN_USAGE.REFERENCED_TABLE_NAME',
            'KEY_COLUMN_USAGE.REFERENCED_COLUMN_NAME',
            'KEY_COLUMN_USAGE.ORDINAL_POSITION',
            'REFERENTIAL_CONSTRAINTS.DELETE_RULE',
            'REFERENTIAL_CONSTRAINTS.UPDATE_RULE',
        ])
        ->where('KEY_COLUMN_USAGE.CONSTRAINT_SCHEMA', DB::getDatabaseName())
        ->where('KEY_COLUMN_USAGE.CONSTRAINT_NAME', $name)
        ->get();
    $row = $rows->first();

    $assert(
        $rows->count() === 1
        && $row !== null
        && $row->TABLE_NAME === $table
        && $row->COLUMN_NAME === $column
        && $row->REFERENCED_TABLE_SCHEMA === DB::getDatabaseName()
        && $row->REFERENCED_TABLE_NAME === $parent
        && $row->REFERENCED_COLUMN_NAME === $parentColumn
        && (int) $row->ORDINAL_POSITION === 1
        && \App\Support\Database\MigrationSchemaGuard::mysqlReferentialActionMatches((string) $row->DELETE_RULE, $deleteRule)
        && \App\Support\Database\MigrationSchemaGuard::mysqlReferentialActionMatches((string) $row->UPDATE_RULE, 'RESTRICT'),
        'foreign key '.$name.' has missing or incompatible source/target columns or referential actions'
    );
}

$auditTables = [
    'security_audit_events',
    'verification_decisions',
    'provenance_review_events',
    'import_promotion_decisions',
    'import_promotion_events',
    'reverification_events',
    'structured_enrichment_promotion_events',
    'official_source_refresh_events',
    'import_conflict_events',
    'import_reconciliation_events',
    'governed_execution_run_events',
];

$expectedTriggers = [];
foreach ($auditTables as $table) {
    $suffix = substr(hash('sha256', $table), 0, 16);
    $expectedTriggers['audit_immut_'.$suffix.'_u'] = [$table, 'UPDATE'];
    $expectedTriggers['audit_immut_'.$suffix.'_d'] = [$table, 'DELETE'];
}
$expectedTriggers['identity_decision_update_guard'] = ['identity_resolution_decisions', 'UPDATE'];
$expectedTriggers['identity_decision_delete_guard'] = ['identity_resolution_decisions', 'DELETE'];

foreach ($expectedTriggers as $name => [$table, $event]) {
    $trigger = DB::table('information_schema.TRIGGERS')
        ->where('TRIGGER_SCHEMA', DB::getDatabaseName())
        ->where('TRIGGER_NAME', $name)
        ->first();

    $assert(
        $trigger !== null
        && $trigger->EVENT_OBJECT_TABLE === $table
        && strtoupper((string) $trigger->EVENT_MANIPULATION) === $event
        && strtoupper((string) $trigger->ACTION_TIMING) === 'BEFORE'
        && strtoupper((string) $trigger->ACTION_ORIENTATION) === 'ROW'
        && str_contains(strtoupper((string) $trigger->ACTION_STATEMENT), 'SIGNAL SQLSTATE'),
        'trigger '.$name.' has missing or incompatible definition'
    );
}

$normalizeMysqlType = static function (string $type): string {
    $type = strtolower(trim($type));

    // MariaDB retains integer display widths; MySQL 8 omits them.
    return preg_replace('/^(tinyint|smallint|mediumint|int|integer|bigint)\\(\\d+\\)/', '$1', $type) ?? $type;
};

$criticalColumns = [
    ['source_snapshots', 'capture_key', 'varchar(64)', 'YES'],
    ['monitoring_policy_runs', 'heartbeat_at', 'timestamp', 'YES'],
    ['users', 'must_change_password', 'tinyint(1)', 'NO'],
    ['import_batches', 'duplicate_rows', 'int unsigned', 'NO'],
    ['source_change_observations', 'current_snapshot_id', 'bigint unsigned', 'NO'],
];

foreach ($criticalColumns as [$table, $column, $type, $nullable]) {
    $definition = DB::table('information_schema.COLUMNS')
        ->where('TABLE_SCHEMA', DB::getDatabaseName())
        ->where('TABLE_NAME', $table)
        ->where('COLUMN_NAME', $column)
        ->first();

    $assert(
        $definition !== null
        && $normalizeMysqlType((string) $definition->COLUMN_TYPE) === $normalizeMysqlType($type)
        && $definition->IS_NULLABLE === $nullable,
        'column '.$table.'.'.$column.' has incompatible type or nullability'
    );
}

if ($failures !== []) {
    fwrite(STDERR, "Phase 1C Test B schema verification failed:\n- ".implode("\n- ", $failures)."\n");
    exit(1);
}

fwrite(STDOUT, 'Verified '.count($requiredTables).' critical tables, '.array_sum(array_map('count', $requiredColumns)).' columns, '.count($requiredIndexes).' indexes, '.count($expectedForeignKeys).' exact foreign keys, '.count($expectedTriggers).' trigger definitions, and critical column metadata. No application row payloads were read.\n');

<?php
declare(strict_types=1);

require_once __DIR__.'/schema-contract-reconciliation.php';

/**
 * Standalone, allowlisted, read-only Phase 1C diagnostic.
 * No Laravel migration classes are loaded or invoked.
 */
function phase1cValidateDatabaseIdentity(?string $actualDatabase, string $expectedDatabase): void
{
    if ($expectedDatabase === '' || !preg_match('/^[A-Za-z0-9_]+$/', $expectedDatabase)) {
        throw new RuntimeException('Expected database identity is missing or invalid.');
    }
    if ($actualDatabase === null || $actualDatabase === '' || !hash_equals($expectedDatabase, $actualDatabase)) {
        throw new RuntimeException('Selected database identity does not match the protected expectation.');
    }
}

function phase1cMetadataVisibilityComplete(array $globalPrivileges, array $schemaPrivileges): bool
{
    $coversSchema = static function (array $privileges): bool {
        foreach ($privileges as $privilege) {
            $value = strtoupper(trim((string)$privilege));
            if ($value === 'SELECT' || $value === 'ALL PRIVILEGES') return true;
        }
        return false;
    };

    return $coversSchema($globalPrivileges) || $coversSchema($schemaPrivileges);
}

function phase1cMetadataAbsenceState(bool $metadataVisibilityComplete): string
{
    return $metadataVisibilityComplete ? 'MISSING' : 'UNKNOWN';
}

function phase1cTriggerMetadataVisibilityComplete(array $globalPrivileges, array $schemaPrivileges): bool
{
    foreach ([$globalPrivileges, $schemaPrivileges] as $privileges) {
        foreach ($privileges as $privilege) {
            $value = strtoupper(trim((string) $privilege));
            if ($value === 'TRIGGER' || $value === 'ALL PRIVILEGES') {
                return true;
            }
        }
    }

    return false;
}

function phase1cGroupMigrationLedgerRows(array $rows): array
{
    $grouped = [];
    foreach ($rows as $row) {
        $migration = (string) $row['migration'];
        $grouped[$migration][] = $row['batch'];
    }

    return $grouped;
}

/**
 * Reconcile the complete deployed migration source inventory with every
 * ledger row. This helper is pure so engine-specific edge cases are tested
 * without a production connection.
 *
 * @param list<string> $sourceNames
 * @param list<array{migration:mixed,batch:mixed,id?:mixed}> $rows
 */
function phase1cAnalyzeFullMigrationLedger(array $sourceNames, array $rows): array
{
    $sourceNames = array_values(array_unique(array_filter(array_map(
        static fn ($name): string => trim((string) $name),
        $sourceNames
    ), static fn (string $name): bool => $name !== '')));
    sort($sourceNames, SORT_STRING);

    $grouped = [];
    $invalidRows = [];
    $orderedByBatch = [];
    $hasSequenceId = true;
    foreach ($rows as $row) {
        $name = trim((string) ($row['migration'] ?? ''));
        $batch = $row['batch'] ?? null;
        if ($name === '') {
            $invalidRows[] = ['reason' => 'EMPTY_MIGRATION_NAME'];
            continue;
        }
        $grouped[$name][] = $batch;
        if (!is_scalar($batch) || !ctype_digit((string) $batch) || (int) $batch < 1) {
            $invalidRows[] = ['migration' => $name, 'reason' => 'INVALID_BATCH_VALUE'];
        } else {
            $orderedByBatch[(int) $batch][] = [
                'migration' => $name,
                'id' => $row['id'] ?? null,
            ];
        }
        if (!array_key_exists('id', $row)) {
            $hasSequenceId = false;
        }
    }

    $ledgerNames = array_keys($grouped);
    sort($ledgerNames, SORT_STRING);
    $missingLedger = array_values(array_diff($sourceNames, $ledgerNames));
    $missingSources = array_values(array_diff($ledgerNames, $sourceNames));
    $duplicates = [];
    foreach ($grouped as $name => $batches) {
        if (count($batches) > 1) {
            $duplicates[] = ['migration' => $name, 'occurrences' => count($batches)];
        }
    }

    $batchNumbers = array_values(array_unique(array_filter(
        array_map(static fn ($batch): int => is_scalar($batch) && ctype_digit((string) $batch) ? (int) $batch : 0, array_column($rows, 'batch')),
        static fn (int $batch): bool => $batch > 0
    )));
    sort($batchNumbers, SORT_NUMERIC);
    $batchGaps = [];
    if ($batchNumbers !== []) {
        for ($batch = $batchNumbers[0]; $batch < $batchNumbers[count($batchNumbers) - 1]; $batch++) {
            if (!in_array($batch, $batchNumbers, true)) {
                $batchGaps[] = $batch;
            }
        }
    }

    $orderingAnomalies = [];
    $orderingChecked = $hasSequenceId && $rows !== [];
    if ($orderingChecked) {
        foreach ($orderedByBatch as $batch => $entries) {
            $previous = null;
            foreach ($entries as $entry) {
                if (!is_scalar($entry['id']) || !ctype_digit((string) $entry['id'])) {
                    $orderingChecked = false;
                    $orderingAnomalies = [];
                    break 2;
                }
                if ($previous !== null && strcmp($previous, $entry['migration']) > 0) {
                    $orderingAnomalies[] = [
                        'batch' => $batch,
                        'previous_migration' => $previous,
                        'migration' => $entry['migration'],
                    ];
                }
                $previous = $entry['migration'];
            }
        }
    }

    $state = 'PASS';
    if ($invalidRows !== [] || $duplicates !== [] || $missingSources !== [] || $orderingAnomalies !== []) {
        $state = 'FAIL';
    } elseif ($missingLedger !== []) {
        $state = 'MISSING';
    }

    return [
        'state' => $state,
        'source_file_count' => count($sourceNames),
        'ledger_row_count' => count($rows),
        'distinct_ledger_migration_count' => count($ledgerNames),
        'source_files_not_recorded' => $missingLedger,
        'ledger_entries_without_source_files' => $missingSources,
        'duplicate_ledger_entries' => $duplicates,
        'invalid_ledger_rows' => $invalidRows,
        'batch_numbers' => $batchNumbers,
        'batch_gaps' => $batchGaps,
        'batch_gap_state' => $batchGaps === [] ? 'PASS' : 'NOT_CHECKED',
        'ordering_state' => $orderingChecked ? ($orderingAnomalies === [] ? 'PASS' : 'FAIL') : 'UNKNOWN',
        'ordering_anomalies' => $orderingAnomalies,
    ];
}

function phase1cClassifyMigrationObjectLedgerState(string $ledgerState, string $objectState): array
{
    if ($ledgerState === 'FAIL') {
        return ['state' => 'FAIL', 'reason' => 'LEDGER_ENTRY_INVALID_OR_DUPLICATED'];
    }
    if ($ledgerState === 'UNKNOWN' || $objectState === 'UNKNOWN') {
        return ['state' => 'UNKNOWN', 'reason' => 'REQUIRED_EVIDENCE_UNAVAILABLE'];
    }
    if ($objectState === 'NOT_CHECKED') {
        return ['state' => 'NOT_CHECKED', 'reason' => 'SOURCE_OBJECT_MAPPING_OR_RECONCILIATION_INCOMPLETE'];
    }
    if ($ledgerState === 'PASS') {
        if ($objectState === 'PASS') {
            return ['state' => 'PASS', 'reason' => 'LEDGER_AND_EXPECTED_OBJECTS_PRESENT'];
        }
        return ['state' => 'FAIL', 'reason' => 'LEDGER_RECORDED_BUT_EXPECTED_OBJECTS_INCOMPLETE'];
    }
    if ($ledgerState === 'MISSING') {
        if ($objectState === 'PASS') {
            return ['state' => 'FAIL', 'reason' => 'EXPECTED_OBJECTS_PRESENT_WITHOUT_LEDGER_ENTRY'];
        }
        if ($objectState === 'MISSING') {
            return ['state' => 'MISSING', 'reason' => 'MIGRATION_AND_EXPECTED_OBJECTS_ABSENT'];
        }
        return ['state' => 'FAIL', 'reason' => 'PARTIAL_OBJECT_APPLICATION_WITHOUT_LEDGER_ENTRY'];
    }

    return ['state' => 'UNKNOWN', 'reason' => 'UNRECOGNIZED_LEDGER_STATE'];
}

function phase1cSafeMetadataDefault(string $column, mixed $value): mixed
{
    // MariaDB may expose an implicit nullable SQL NULL as the unquoted
    // metadata sentinel "NULL". Preserve that sentinel for structural
    // normalization; it is not a credential value.
    $isSqlNullSentinel = is_string($value) && strtoupper(trim($value)) === 'NULL';
    if ($column !== 'must_change_password' && $value !== null && !$isSqlNullSentinel
        && preg_match('/password|secret|token|credential|private.?key|api.?key/i', $column)) {
        return '[REDACTED]';
    }

    return $value;
}

function phase1cMysqlNullDefaultFromDefinition(?string $columnDefinition): ?bool
{
    if ($columnDefinition === null || preg_match("/\\bDEFAULT\\s+(NULL|'(?:''|[^'])*')(?=\\s|,|$)/i", $columnDefinition, $matches) !== 1) {
        return null;
    }

    $default = trim($matches[1]);
    if (strtoupper($default) === 'NULL') {
        return true;
    }

    return str_starts_with($default, "'") ? false : null;
}

final class Phase1CReadOnlyDiagnostic
{
    private const TABLES = [
        'accreditations','admission_intakes','assertions','campuses','change_events','data_quality_events',
        'data_reverification_queue','data_reverification_queue_identities','eligibility_rules','enrichment_import_rows',
        'evidence','external_rankings','fee_records','geographies','governed_execution_leases',
        'governed_execution_run_events','governed_execution_runs','identity_match_candidates',
        'identity_resolution_decisions','import_batches','import_conflict_events','import_conflicts',
        'import_promotion_decisions','import_promotion_events','import_reconciliation_events','import_rows',
        'import_schema_profiles','institute_discovery_run_outcomes','institution_field_approvals','institution_names',
        'institutions','merit_records','migrations','moderation_actions','monitoring_alert_identities',
        'monitoring_alerts','monitoring_policy_run_sources','monitoring_policy_runs','official_source_refresh_events',
        'password_reset_tokens','program_offerings','programs','provenance_review_events','ranking_methodologies',
        'recommendation_policies','recommendations','release_records','reverification_events','review_ratings',
        'reviews','scholarships','security_audit_events','selection_outcomes','source_change_assertion_links',
        'source_change_observations','source_monitoring_policies','source_retrieval_runs',
        'source_snapshot_reconciliation_requests','source_snapshots','sources','structured_enrichment_promotion_events',
        'users','verification_decisions'
    ];

    private const EXPECTED_COLUMNS = [
        ['password_reset_tokens', 'email', 'varchar(255)', false],
        ['password_reset_tokens', 'token', 'varchar(255)', false],
        ['password_reset_tokens', 'created_at', 'timestamp', true, null],
        ['users', 'must_change_password', 'tinyint(1)', false, '0'],
        ['import_batches', 'duplicate_rows', 'int unsigned', false, '0'],
        ['structured_enrichment_promotion_events', 'enrichment_import_row_id', 'bigint unsigned', false],
        ['structured_enrichment_promotion_events', 'import_batch_id', 'bigint unsigned', false],
        ['structured_enrichment_promotion_events', 'occurred_at', 'timestamp', false],
        ['governed_execution_leases', 'task_key', 'varchar(120)', false],
        ['governed_execution_leases', 'owner_token', 'char(36)', false],
        ['governed_execution_leases', 'run_id', 'char(36)', false],
        ['governed_execution_leases', 'expires_at', 'timestamp', false],
        ['governed_execution_runs', 'id', 'char(36)', false],
        ['governed_execution_run_events', 'run_id', 'char(36)', false],
        ['governed_execution_run_events', 'event_type', 'varchar(30)', false],
        ['governed_execution_run_events', 'occurred_at', 'timestamp', false],
        ['source_retrieval_runs', 'idempotency_key', 'varchar(64)', true, null],
        ['source_snapshots', 'capture_key', 'varchar(64)', true, null],
        ['source_change_observations', 'current_snapshot_id', 'bigint unsigned', false],
        ['institute_discovery_run_outcomes', 'idempotency_key', 'varchar(64)', true, null],
        ['change_events', 'source_change_assertion_link_id', 'bigint unsigned', true, null],
        ['data_reverification_queue_identities', 'identity_key', 'varchar(64)', false],
        ['data_reverification_queue_identities', 'subject_type', 'varchar(255)', false],
        ['data_reverification_queue_identities', 'subject_id', 'bigint unsigned', false],
        ['data_reverification_queue_identities', 'active_queue_id', 'bigint unsigned', true, null],
        ['monitoring_alert_identities', 'dedupe_key', 'varchar(40)', false],
        ['monitoring_alert_identities', 'active_alert_id', 'bigint unsigned', true, null],
        ['security_audit_events', 'monitoring_idempotency_key', 'varchar(191)', true, null],
        ['monitoring_policy_runs', 'heartbeat_at', 'timestamp', true, null],
        ['monitoring_policy_runs', 'source_monitoring_policy_id', 'bigint unsigned', false],
        ['monitoring_policy_runs', 'execution_run_id', 'char(36)', true, null],
        ['monitoring_policy_runs', 'started_at', 'timestamp', false],
        ['monitoring_policy_run_sources', 'monitoring_policy_run_id', 'bigint unsigned', false],
        ['monitoring_policy_run_sources', 'source_id', 'bigint unsigned', false],
        ['monitoring_policy_run_sources', 'retrieval_run_id', 'bigint unsigned', true, null],
        ['monitoring_policy_run_sources', 'observation_id', 'bigint unsigned', true, null],
        ['import_batches', 'discovery_identity_key', 'varchar(64)', true, null],
        ['evidence', 'discovery_identity_key', 'varchar(64)', true, null],
        ['import_rows', 'discovery_identity_key', 'varchar(64)', true, null],
        ['assertions', 'discovery_identity_key', 'varchar(64)', true, null],
        ['identity_match_candidates', 'identity_key', 'char(64)', true, null],
        ['identity_resolution_decisions', 'decision_identity_key', 'char(64)', true, null],
        ['import_conflicts', 'identity_key', 'char(64)', true, null],
        ['import_conflict_events', 'idempotency_key', 'char(64)', false],
        ['source_snapshot_reconciliation_requests', 'identity_key', 'varchar(64)', false],
        ['source_snapshot_reconciliation_requests', 'source_id', 'bigint unsigned', false],
        ['source_snapshot_reconciliation_requests', 'triggering_snapshot_id', 'bigint unsigned', false],
        ['source_snapshot_reconciliation_requests', 'current_snapshot_id', 'bigint unsigned', false],
        ['source_snapshot_reconciliation_requests', 'expected_previous_snapshot_id', 'bigint unsigned', true, null],
        ['source_snapshot_reconciliation_requests', 'recorded_previous_snapshot_id', 'bigint unsigned', true, null],
    ];

    private const EXPECTED_FOREIGN_KEYS = [
        ['identity_decision_row_restrict_fk', 'identity_resolution_decisions', 'import_row_id', 'import_rows', 'id', 'RESTRICT'],
        ['verification_decision_assertion_restrict_fk', 'verification_decisions', 'assertion_id', 'assertions', 'id', 'RESTRICT'],
        ['import_reconciliation_batch_restrict_fk', 'import_reconciliation_events', 'import_batch_id', 'import_batches', 'id', 'RESTRICT'],
        ['import_promotion_decision_row_restrict_fk', 'import_promotion_decisions', 'import_row_id', 'import_rows', 'id', 'RESTRICT'],
        ['import_promotion_event_row_restrict_fk', 'import_promotion_events', 'import_row_id', 'import_rows', 'id', 'RESTRICT'],
        ['import_promotion_event_batch_restrict_fk', 'import_promotion_events', 'import_batch_id', 'import_batches', 'id', 'RESTRICT'],
        ['reverification_event_queue_restrict_fk', 'reverification_events', 'queue_id', 'data_reverification_queue', 'id', 'RESTRICT'],
        ['structured_enrichment_row_restrict_fk', 'structured_enrichment_promotion_events', 'enrichment_import_row_id', 'enrichment_import_rows', 'id', 'RESTRICT'],
        ['structured_enrichment_batch_restrict_fk', 'structured_enrichment_promotion_events', 'import_batch_id', 'import_batches', 'id', 'RESTRICT'],
        ['import_conflict_event_restrict_fk', 'import_conflict_events', 'import_conflict_id', 'import_conflicts', 'id', 'RESTRICT'],
        ['governed_run_event_restrict_fk', 'governed_execution_run_events', 'run_id', 'governed_execution_runs', 'id', 'RESTRICT'],
        ['ssr_source_fk', 'source_snapshot_reconciliation_requests', 'source_id', 'sources', 'id', 'CASCADE'],
        ['ssr_triggering_snapshot_fk', 'source_snapshot_reconciliation_requests', 'triggering_snapshot_id', 'source_snapshots', 'id', 'CASCADE'],
        ['ssr_current_snapshot_fk', 'source_snapshot_reconciliation_requests', 'current_snapshot_id', 'source_snapshots', 'id', 'CASCADE'],
        ['ssr_expected_previous_fk', 'source_snapshot_reconciliation_requests', 'expected_previous_snapshot_id', 'source_snapshots', 'id', 'SET NULL'],
        ['ssr_recorded_previous_fk', 'source_snapshot_reconciliation_requests', 'recorded_previous_snapshot_id', 'source_snapshots', 'id', 'SET NULL'],
        ['mp_run_policy_fk', 'monitoring_policy_runs', 'source_monitoring_policy_id', 'source_monitoring_policies', 'id', 'RESTRICT'],
        ['mp_run_exec_fk', 'monitoring_policy_runs', 'execution_run_id', 'governed_execution_runs', 'id', 'SET NULL'],
        ['mp_source_run_fk', 'monitoring_policy_run_sources', 'monitoring_policy_run_id', 'monitoring_policy_runs', 'id', 'RESTRICT'],
        ['mp_source_source_fk', 'monitoring_policy_run_sources', 'source_id', 'sources', 'id', 'RESTRICT'],
        ['mp_source_retrieval_fk', 'monitoring_policy_run_sources', 'retrieval_run_id', 'source_retrieval_runs', 'id', 'SET NULL'],
        ['mp_source_observation_fk', 'monitoring_policy_run_sources', 'observation_id', 'source_change_observations', 'id', 'SET NULL'],
        ['mai_active_alert_fk', 'monitoring_alert_identities', 'active_alert_id', 'monitoring_alerts', 'id', 'SET NULL'],
    ];

    private const AUDIT_TRIGGER_TABLES = [
        'security_audit_events', 'verification_decisions', 'provenance_review_events',
        'import_promotion_decisions', 'import_promotion_events', 'reverification_events',
        'structured_enrichment_promotion_events', 'official_source_refresh_events',
        'import_conflict_events', 'import_reconciliation_events', 'governed_execution_run_events',
    ];

    private const EXPECTED_MIGRATIONS = [
        '2026_10_17_000001_create_password_reset_tokens_table',
        '2026_10_17_000002_add_must_change_password_to_users_table',
        '2026_10_23_000001_add_duplicate_rows_to_import_batches_table',
        '2026_10_25_000001_create_structured_enrichment_promotion_events_table',
        '2026_11_03_000001_create_governed_execution_leases_table',
        '2026_11_03_000002_create_governed_execution_runs_table',
        '2026_11_03_000003_add_source_retrieval_idempotency_key',
        '2026_11_03_000004_add_snapshot_and_observation_idempotency',
        '2026_11_03_000005_add_discovery_outcome_idempotency_key',
        '2026_11_03_000006_add_change_event_source_link_identity',
        '2026_11_03_000007_create_data_reverification_queue_identities_table',
        '2026_11_04_000009_create_monitoring_alert_identities_and_audit_idempotency',
        '2026_11_04_000010_create_monitoring_policy_run_checkpoints',
        '2026_11_04_000011_add_discovery_candidate_persistence_identity_keys',
        '2026_11_04_000012_add_identity_conflict_persistence_keys_and_events',
        '2026_11_04_000013_create_source_snapshot_reconciliation_requests_table',
        '2026_11_04_000014_enforce_append_only_audit_retention',
        '2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat',
    ];

    private const INDEXES = [
        ['password_reset_tokens','PRIMARY',['email'],true],
        ['governed_execution_leases','governed_execution_leases_task_key_unique',['task_key'],true],
        ['governed_execution_leases','governed_execution_leases_expires_at_index',['expires_at'],false],
        ['source_retrieval_runs','source_retrieval_runs_idempotency_key_unique',['idempotency_key'],true],
        ['source_snapshots','source_snapshots_capture_key_unique',['capture_key'],true],
        ['source_change_observations','source_observations_current_snapshot_unique',['current_snapshot_id'],true],
        ['institute_discovery_run_outcomes','institute_discovery_run_outcomes_idempotency_key_unique',['idempotency_key'],true],
        ['change_events','change_events_source_link_unique',['source_change_assertion_link_id'],true],
        ['security_audit_events','security_audit_events_monitoring_idempotency_key_unique',['monitoring_idempotency_key'],true],
        ['monitoring_policy_run_sources','monitoring_policy_run_source_unique',['monitoring_policy_run_id','source_id'],true],
        ['import_batches','import_batches_discovery_identity_unique',['discovery_identity_key'],true],
        ['evidence','evidence_discovery_identity_unique',['discovery_identity_key'],true],
        ['import_rows','import_rows_discovery_identity_unique',['discovery_identity_key'],true],
        ['assertions','assertions_discovery_identity_unique',['discovery_identity_key'],true],
        ['identity_match_candidates','identity_match_candidates_identity_key_unique',['identity_key'],true],
        ['identity_resolution_decisions','identity_resolution_decisions_identity_key_unique',['decision_identity_key'],true],
        ['import_conflicts','import_conflicts_identity_key_unique',['identity_key'],true],
        ['import_conflict_events','import_conflict_events_idempotency_key_unique',['idempotency_key'],true],
        ['source_snapshot_reconciliation_requests','source_snapshot_reconciliation_requests_identity_key_unique',['identity_key'],true],
        ['source_snapshot_reconciliation_requests','snapshot_reconciliation_source_status_idx',['source_id','status'],false],
        ['source_snapshot_reconciliation_requests','snapshot_reconciliation_current_status_idx',['current_snapshot_id','status'],false],
        ['import_conflict_events','import_conflict_events_import_conflict_id_occurred_at_index',['import_conflict_id','occurred_at'],false],
        ['monitoring_policy_run_sources','monitoring_policy_source_status_idx',['source_id','status'],false],
    ];

    /** @var list<array{id:string,tables:list<string>,columns:array<string,list<string>>,sql:string}> */
    private const ORPHANS = [
        ['orphan_identity_decisions_without_import_rows',['identity_resolution_decisions','import_rows'],['identity_resolution_decisions'=>['import_row_id'],'import_rows'=>['id']],'SELECT EXISTS(SELECT 1 FROM identity_resolution_decisions d LEFT JOIN import_rows r ON r.id=d.import_row_id WHERE r.id IS NULL LIMIT 1)'],
        ['orphan_verification_decisions_without_assertions',['verification_decisions','assertions'],['verification_decisions'=>['assertion_id'],'assertions'=>['id']],'SELECT EXISTS(SELECT 1 FROM verification_decisions d LEFT JOIN assertions a ON a.id=d.assertion_id WHERE a.id IS NULL LIMIT 1)'],
        ['orphan_reconciliation_events_without_batches',['import_reconciliation_events','import_batches'],['import_reconciliation_events'=>['import_batch_id'],'import_batches'=>['id']],'SELECT EXISTS(SELECT 1 FROM import_reconciliation_events e LEFT JOIN import_batches b ON b.id=e.import_batch_id WHERE b.id IS NULL LIMIT 1)'],
        ['orphan_promotion_decisions_without_rows',['import_promotion_decisions','import_rows'],['import_promotion_decisions'=>['import_row_id'],'import_rows'=>['id']],'SELECT EXISTS(SELECT 1 FROM import_promotion_decisions d LEFT JOIN import_rows r ON r.id=d.import_row_id WHERE r.id IS NULL LIMIT 1)'],
        ['orphan_promotion_events_without_rows',['import_promotion_events','import_rows'],['import_promotion_events'=>['import_row_id'],'import_rows'=>['id']],'SELECT EXISTS(SELECT 1 FROM import_promotion_events e LEFT JOIN import_rows r ON r.id=e.import_row_id WHERE r.id IS NULL LIMIT 1)'],
        ['orphan_promotion_events_without_batches',['import_promotion_events','import_batches'],['import_promotion_events'=>['import_batch_id'],'import_batches'=>['id']],'SELECT EXISTS(SELECT 1 FROM import_promotion_events e LEFT JOIN import_batches b ON b.id=e.import_batch_id WHERE b.id IS NULL LIMIT 1)'],
        ['orphan_reverification_events_without_queue_rows',['reverification_events','data_reverification_queue'],['reverification_events'=>['queue_id'],'data_reverification_queue'=>['id']],'SELECT EXISTS(SELECT 1 FROM reverification_events e LEFT JOIN data_reverification_queue q ON q.id=e.queue_id WHERE q.id IS NULL LIMIT 1)'],
        ['orphan_structured_enrichment_events_without_rows',['structured_enrichment_promotion_events','enrichment_import_rows'],['structured_enrichment_promotion_events'=>['enrichment_import_row_id'],'enrichment_import_rows'=>['id']],'SELECT EXISTS(SELECT 1 FROM structured_enrichment_promotion_events e LEFT JOIN enrichment_import_rows r ON r.id=e.enrichment_import_row_id WHERE r.id IS NULL LIMIT 1)'],
        ['orphan_structured_enrichment_events_without_batches',['structured_enrichment_promotion_events','import_batches'],['structured_enrichment_promotion_events'=>['import_batch_id'],'import_batches'=>['id']],'SELECT EXISTS(SELECT 1 FROM structured_enrichment_promotion_events e LEFT JOIN import_batches b ON b.id=e.import_batch_id WHERE b.id IS NULL LIMIT 1)'],
        ['orphan_conflict_events_without_conflicts',['import_conflict_events','import_conflicts'],['import_conflict_events'=>['import_conflict_id'],'import_conflicts'=>['id']],'SELECT EXISTS(SELECT 1 FROM import_conflict_events e LEFT JOIN import_conflicts c ON c.id=e.import_conflict_id WHERE c.id IS NULL LIMIT 1)'],
        ['orphan_governed_run_events_without_runs',['governed_execution_run_events','governed_execution_runs'],['governed_execution_run_events'=>['run_id'],'governed_execution_runs'=>['id']],'SELECT EXISTS(SELECT 1 FROM governed_execution_run_events e LEFT JOIN governed_execution_runs r ON r.id=e.run_id WHERE r.id IS NULL LIMIT 1)'],
        ['orphan_monitoring_runs_without_policy',['monitoring_policy_runs','source_monitoring_policies'],['monitoring_policy_runs'=>['source_monitoring_policy_id'],'source_monitoring_policies'=>['id']],'SELECT EXISTS(SELECT 1 FROM monitoring_policy_runs r LEFT JOIN source_monitoring_policies p ON p.id=r.source_monitoring_policy_id WHERE p.id IS NULL LIMIT 1)'],
        ['orphan_monitoring_runs_without_governed_run',['monitoring_policy_runs','governed_execution_runs'],['monitoring_policy_runs'=>['execution_run_id'],'governed_execution_runs'=>['id']],'SELECT EXISTS(SELECT 1 FROM monitoring_policy_runs r LEFT JOIN governed_execution_runs g ON g.id=r.execution_run_id WHERE r.execution_run_id IS NOT NULL AND g.id IS NULL LIMIT 1)'],
        ['orphan_monitoring_sources_without_policy_run',['monitoring_policy_run_sources','monitoring_policy_runs'],['monitoring_policy_run_sources'=>['monitoring_policy_run_id'],'monitoring_policy_runs'=>['id']],'SELECT EXISTS(SELECT 1 FROM monitoring_policy_run_sources s LEFT JOIN monitoring_policy_runs r ON r.id=s.monitoring_policy_run_id WHERE r.id IS NULL LIMIT 1)'],
        ['orphan_monitoring_sources_without_source',['monitoring_policy_run_sources','sources'],['monitoring_policy_run_sources'=>['source_id'],'sources'=>['id']],'SELECT EXISTS(SELECT 1 FROM monitoring_policy_run_sources s LEFT JOIN sources x ON x.id=s.source_id WHERE x.id IS NULL LIMIT 1)'],
        ['orphan_monitoring_sources_without_retrieval_run',['monitoring_policy_run_sources','source_retrieval_runs'],['monitoring_policy_run_sources'=>['retrieval_run_id'],'source_retrieval_runs'=>['id']],'SELECT EXISTS(SELECT 1 FROM monitoring_policy_run_sources s LEFT JOIN source_retrieval_runs r ON r.id=s.retrieval_run_id WHERE s.retrieval_run_id IS NOT NULL AND r.id IS NULL LIMIT 1)'],
        ['orphan_monitoring_sources_without_observation',['monitoring_policy_run_sources','source_change_observations'],['monitoring_policy_run_sources'=>['observation_id'],'source_change_observations'=>['id']],'SELECT EXISTS(SELECT 1 FROM monitoring_policy_run_sources s LEFT JOIN source_change_observations o ON o.id=s.observation_id WHERE s.observation_id IS NOT NULL AND o.id IS NULL LIMIT 1)'],
        ['orphan_monitoring_alert_identities_without_active_alert',['monitoring_alert_identities','monitoring_alerts'],['monitoring_alert_identities'=>['active_alert_id'],'monitoring_alerts'=>['id']],'SELECT EXISTS(SELECT 1 FROM monitoring_alert_identities i LEFT JOIN monitoring_alerts a ON a.id=i.active_alert_id WHERE i.active_alert_id IS NOT NULL AND a.id IS NULL LIMIT 1)'],
    ];

    /** @var list<array{id:string,table:string,columns:list<string>,where:string}> */
    private const DUPLICATES = [
        ['duplicate_open_reverification_identity','data_reverification_queue',['subject_type','subject_id','attribute'],"status='open'"],
        ['duplicate_open_monitoring_alert_dedupe_key','monitoring_alerts',['dedupe_key'],"status='open'"],
        ['duplicate_observation_current_snapshot_id','source_change_observations',['current_snapshot_id'],'current_snapshot_id IS NOT NULL'],
        ['duplicate_source_retrieval_idempotency_key','source_retrieval_runs',['idempotency_key'],'idempotency_key IS NOT NULL'],
        ['duplicate_source_snapshot_capture_key','source_snapshots',['capture_key'],'capture_key IS NOT NULL'],
        ['duplicate_discovery_outcome_idempotency_key','institute_discovery_run_outcomes',['idempotency_key'],'idempotency_key IS NOT NULL'],
        ['duplicate_change_event_source_link','change_events',['source_change_assertion_link_id'],'source_change_assertion_link_id IS NOT NULL'],
        ['duplicate_import_batch_discovery_identity','import_batches',['discovery_identity_key'],'discovery_identity_key IS NOT NULL'],
        ['duplicate_evidence_discovery_identity','evidence',['discovery_identity_key'],'discovery_identity_key IS NOT NULL'],
        ['duplicate_import_row_discovery_identity','import_rows',['discovery_identity_key'],'discovery_identity_key IS NOT NULL'],
        ['duplicate_assertion_discovery_identity','assertions',['discovery_identity_key'],'discovery_identity_key IS NOT NULL'],
        ['duplicate_identity_candidate_key','identity_match_candidates',['identity_key'],'identity_key IS NOT NULL'],
        ['duplicate_identity_decision_key','identity_resolution_decisions',['decision_identity_key'],'decision_identity_key IS NOT NULL'],
        ['duplicate_import_conflict_identity_key','import_conflicts',['identity_key'],'identity_key IS NOT NULL'],
    ];

    private PDO $pdo;
    private array $tables=[];
    private array $columns=[];
    private array $columnErrors=[];
    private array $indexErrors=[];
    private bool $absenceProof=false;
    private bool $triggerAbsenceProof=false;
    private string $databaseName='';

    public function __construct(PDO $pdo) { $this->pdo=$pdo; }

    public function collect(string $expectedDatabase): array
    {
        $this->pdo->exec('SET TRANSACTION READ ONLY');
        $this->pdo->beginTransaction();
        try {
            // Verify the actual selected schema before any schema-inventory query.
            $selectedDatabase = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
            phase1cValidateDatabaseIdentity(is_string($selectedDatabase) ? $selectedDatabase : null, $expectedDatabase);
            $this->databaseName = $selectedDatabase;
            $version=(string)$this->pdo->query('SELECT VERSION()')->fetchColumn();
            $this->inventory();
            $ledgerReconciliation = $this->migrationLedgerReconciliation();
            $sourceSchemaReconciliation = $this->sourceSchemaReconciliation();
            $targetMigrationObjects = $this->migrationObjectReconciliation($ledgerReconciliation);
            $fullSourceMigrationReconciliation = $this->fullSourceMigrationReconciliation($targetMigrationObjects, $sourceSchemaReconciliation);
            $result=[
                'schema_version'=>'phase1c-readonly-diagnostic/v3',
                'database'=>['engine'=>stripos($version,'mariadb')!==false?'mariadb':'mysql','server_version'=>$version,'identity_verified'=>true],
                'metadata_visibility'=>['complete'=>$this->absenceProof,'triggers_complete'=>$this->triggerAbsenceProof],
                'tables'=>$this->tableMetadata(),
                'columns'=>$this->columnMetadata(),
                'indexes'=>$this->indexMetadata(),
                'expected_index_reconciliation'=>$this->indexReconciliation(),
                'expected_column_reconciliation'=>$this->columnReconciliation(),
                'foreign_keys'=>$this->foreignKeys(),
                'expected_foreign_key_reconciliation'=>$this->foreignKeyReconciliation(),
                'expected_trigger_reconciliation'=>$this->triggerReconciliation(),
                'migration_ledger_reconciliation'=>$ledgerReconciliation,
                'migration_object_reconciliation'=>$targetMigrationObjects,
                'full_source_migration_reconciliation'=>$fullSourceMigrationReconciliation,
                'full_migration_ledger_inventory'=>$this->fullMigrationLedgerInventory(),
                'source_schema_reconciliation'=>$sourceSchemaReconciliation,
                'queue_mapping'=>$this->queueMapping(),
                'alert_mapping'=>$this->alertMapping(),
                'orphan_checks'=>$this->orphanChecks(),
                'duplicate_checks'=>$this->duplicateChecks(),
                'registry_counts'=>[
                    'tables'=>count(self::TABLES),
                    'expected_indexes'=>count(self::INDEXES),
                    'expected_columns'=>count(self::EXPECTED_COLUMNS),
                    'expected_foreign_keys'=>count(self::EXPECTED_FOREIGN_KEYS),
                    'expected_triggers'=>count($this->expectedTriggers()),
                    'expected_migrations'=>count(self::EXPECTED_MIGRATIONS),
                    'orphan_checks'=>count(self::ORPHANS),
                    'duplicate_checks'=>count(self::DUPLICATES),
                ],
                'transaction'=>['mode'=>'READ ONLY','outcome'=>'ROLLED_BACK'],
            ];
            $this->pdo->rollBack();
            return $this->sortKeys($result);
        } catch(Throwable $e) {
            if($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        } finally {
            if($this->pdo->inTransaction()) $this->pdo->rollBack();
        }
    }

    /**
     * Reconcile every migration source filename with the complete ledger.
     * Object states outside the existing, independently checked 18-target
     * registry remain NOT_CHECKED rather than being inferred from ledger rows.
     */
    private function fullSourceMigrationReconciliation(array $targetObjects, array $sourceSchema): array
    {
        $unknown = ['state'=>'UNKNOWN','reason'=>'FULL_SOURCE_MIGRATION_RECONCILIATION_UNAVAILABLE','source_count'=>null,'ledger_row_count'=>null,'source_files_not_recorded'=>[],'ledger_entries_without_source_files'=>[],'duplicate_ledger_entries'=>[],'invalid_ledger_rows'=>[],'batch_numbers'=>[],'batch_gaps'=>[],'batch_gap_state'=>'UNKNOWN','ordering_state'=>'UNKNOWN','migration_object_reconciliation'=>[],'object_mapping_state'=>'NOT_CHECKED'];
        if (!isset($this->tables['migrations']) || isset($this->columnErrors['migrations']) || !$this->has('migrations',['migration','batch'])) {
            $unknown['reason'] = 'MIGRATION_LEDGER_OR_METADATA_UNAVAILABLE';
            return $unknown;
        }
        try {
            $path = dirname(__DIR__, 2).'/database/schema-contract/phase1d2b-migration-object-provenance.json';
            if (!is_file($path) || !is_readable($path)) {
                $unknown['reason'] = 'MIGRATION_OBJECT_PROVENANCE_UNAVAILABLE';
                return $unknown;
            }
            $provenance = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $entries = $provenance['migrations'] ?? null;
            if (!is_array($entries) || count($entries) !== 97) {
                $unknown['reason'] = 'MIGRATION_OBJECT_PROVENANCE_INCOMPLETE';
                return $unknown;
            }
            $hasId = isset($this->columns['migrations']['id']);
            $rows = $this->pdo->query($hasId
                ? 'SELECT id,migration,batch FROM migrations ORDER BY id'
                : 'SELECT migration,batch FROM migrations ORDER BY batch,migration'
            )->fetchAll(PDO::FETCH_ASSOC);
            $targetStates = [];
            foreach ($targetObjects as $target) {
                if (isset($target['migration'], $target['object_state'])) $targetStates[$target['migration']] = (string)$target['object_state'];
            }

            $manifestObjectStates = [];
            foreach (($sourceSchema['results'] ?? []) as $table => $tableResult) {
                foreach (['columns','indexes','foreign_keys','triggers'] as $kind) {
                    foreach (($tableResult['objects'][$kind] ?? []) as $objectResult) {
                        if (isset($objectResult['object'], $objectResult['state'])) {
                            $manifestObjectStates[$table.'.'.$objectResult['object']] = (string)$objectResult['state'];
                        }
                    }
                }
                $manifestObjectStates[$table.'.__table__'] = (string)($tableResult['objects']['table']['state'] ?? 'NOT_CHECKED');
            }

            $migrationObjects = [];
            $objectDetailsByMigration = [];
            $reviewStatesByMigration = [];
            foreach ($entries as $entry) {
                $name = (string)($entry['migration'] ?? '');
                if ($name === '') continue;
                $reviewState = (string)($entry['object_mapping_state'] ?? 'NOT_CHECKED');
                $reviewStatesByMigration[$name] = $reviewState;
                if ($reviewState === 'REVIEWED_NOOP') {
                    $objectState = 'PASS';
                    $objectDetailsByMigration[$name] = [];
                } else {
                    $objectRefs = [];
                    foreach ([
                        'columns_created'=>'columns','columns_changed'=>'columns',
                        'indexes_created'=>'indexes',
                        'foreign_keys_created'=>'foreign_keys','foreign_keys_changed'=>'foreign_keys',
                        'triggers_installed'=>'triggers',
                        'conditional_existing_objects'=>'columns',
                    ] as $field => $kind) {
                        foreach (($entry[$field] ?? []) as $qualified) {
                            $parts = explode('.', (string)$qualified, 2);
                            if (count($parts) === 2) {
                                $objectRefs[] = [
                                    'table'=>$parts[0],
                                    'kind'=>$kind,
                                    'name'=>$parts[1],
                                    'relation'=>$field === 'conditional_existing_objects' ? 'CONDITIONAL_EXISTING' : 'SOURCE_OPERATION',
                                ];
                            }
                        }
                    }
                    foreach (($entry['tables_created'] ?? []) as $tableName) {
                        $objectRefs[] = ['table'=>(string)$tableName,'kind'=>'table','name'=>'__table__','relation'=>'TABLE_CREATED'];
                    }
                    foreach (($entry['tables_altered'] ?? []) as $tableName) {
                        $objectRefs[] = ['table'=>(string)$tableName,'kind'=>'table','name'=>'__table__','relation'=>'TABLE_ALTERED'];
                    }
                    $objectStates = [];
                    $objectDetails = [];
                    foreach ($objectRefs as $ref) {
                        $key = $ref['table'].'.'.$ref['name'];
                        $state = $manifestObjectStates[$key] ?? 'NOT_CHECKED';
                        $objectStates[] = $state;
                        $objectDetails[] = ['object'=>$ref['kind'].':'.$key,'state'=>$state,'relation'=>$ref['relation'] ?? 'SOURCE_OPERATION'];
                    }
                    $objectDetailsByMigration[$name] = $objectDetails;
                    $targetState = $targetStates[$name] ?? null;
                    if (in_array($targetState, ['FAIL','UNKNOWN','MISSING'], true)) $objectStates[] = $targetState;
                    $objectState = in_array('FAIL', $objectStates, true) || in_array('MISSING', $objectStates, true) ? 'FAIL'
                        : (in_array('UNKNOWN', $objectStates, true) ? 'UNKNOWN'
                        : (in_array('NOT_CHECKED', $objectStates, true) ? 'NOT_CHECKED'
                        : ($reviewState === 'SOURCE_REVIEWED_COMPLETE' && $objectStates !== [] && empty($entry['operation_review']['unresolved']) ? 'PASS' : 'NOT_CHECKED')));
                }
                $migrationObjects[$name] = [$objectState];
            }

            $analysis = Phase1D2BSchemaContractComparator::reconcileMigrationLedger(
                array_map(static fn(array $entry): string => (string)$entry['migration'], $entries),
                $rows,
                $migrationObjects,
                true,
                $hasId
            );
            foreach ($analysis['migration_object_reconciliation'] as &$migrationResult) {
                $name = (string)$migrationResult['migration'];
                $migrationResult['source_review_state'] = $reviewStatesByMigration[$name] ?? 'NOT_CHECKED';
                $migrationResult['objects'] = $objectDetailsByMigration[$name] ?? [];
            }
            unset($migrationResult);
            $states = array_column($analysis['migration_object_reconciliation'], 'object_state');
            $analysis['object_mapping_state'] = in_array('FAIL', $states, true) ? 'FAIL'
                : (in_array('UNKNOWN', $states, true) ? 'UNKNOWN'
                : (in_array('NOT_CHECKED', $states, true) ? 'NOT_CHECKED' : 'PASS'));
            $analysis['object_state_counts'] = array_count_values($states);
            $analysis['source_review_state_counts'] = array_count_values(array_values($reviewStatesByMigration));
            $analysis['provenance_mapping_state'] = (string)($provenance['coverage_state'] ?? 'NOT_CHECKED');
            return $analysis;
        } catch (Throwable $e) {
            $unknown['reason'] = 'FULL_SOURCE_MIGRATION_RECONCILIATION_FAILED';
            $unknown['error'] = $this->safeError($e);
            return $unknown;
        }
    }

    /**
     * Emit the full source-manifest comparison state without converting
     * incomplete source definitions into false PASS or MISSING outcomes.
     * The existing collector remains allowlisted and read-only.
     */
    private function sourceSchemaReconciliation(): array
    {
        $path = dirname(__DIR__, 2).'/database/schema-contract/phase1d2b-final-schema-manifest.json';
        if (!is_file($path) || !is_readable($path)) {
            return ['contract'=>'phase1d2b-schema-comparison/v1','state'=>'UNKNOWN','reason'=>'SOURCE_SCHEMA_MANIFEST_UNAVAILABLE','table_count'=>0,'complete_table_count'=>0,'results'=>[],'definition_coverage'=>['state'=>'UNKNOWN'],'observed_metadata_collected'=>false,'schema_equivalence_proven'=>false];
        }
        try {
            $manifest = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($manifest) || ($manifest['contract'] ?? null) !== 'phase1d2b-final-schema-manifest/v1') {
                return ['contract'=>'phase1d2b-schema-comparison/v1','state'=>'UNKNOWN','reason'=>'SOURCE_SCHEMA_MANIFEST_INVALID','table_count'=>0,'complete_table_count'=>0,'results'=>[],'definition_coverage'=>['state'=>'UNKNOWN'],'observed_metadata_collected'=>false,'schema_equivalence_proven'=>false];
            }
            $observations = $this->observedSchemaContract();
            $comparison = Phase1D2BSchemaContractComparator::reconcileManifest(
                $manifest,
                $observations['observed'],
                $observations['visibility']
            );
            $comparison['definition_coverage'] = $manifest['definition_coverage'] ?? ['state'=>'UNKNOWN'];
            $comparison['observed_metadata_collected'] = $observations['observed']['tables'] !== [];
            $comparison['schema_equivalence_proven'] = $comparison['state'] === 'PASS'
                && $comparison['complete_table_count'] === 63;
            return $comparison;
        } catch (Throwable $e) {
            return ['contract'=>'phase1d2b-schema-comparison/v1','state'=>'UNKNOWN','reason'=>'SOURCE_SCHEMA_MANIFEST_READ_FAILED','table_count'=>0,'complete_table_count'=>0,'results'=>[],'definition_coverage'=>['state'=>'UNKNOWN'],'observed_metadata_collected'=>false,'schema_equivalence_proven'=>false];
        }
    }

    private function observedSchemaContract(): array
    {
        $observed = ['tables'=>[], 'columns'=>[], 'indexes'=>[], 'foreign_keys'=>[], 'triggers'=>[], 'server_defaults'=>[]];
        try {
            $observed['server_defaults']['engine'] = (string)$this->pdo->query('SELECT @@default_storage_engine')->fetchColumn();
        } catch (Throwable $e) {
            $observed['server_defaults']['engine'] = null;
        }

        $tableRows = $this->pdo->query(
            "SELECT TABLE_NAME, ENGINE, TABLE_COLLATION, CREATE_OPTIONS FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tableRows as $row) {
            $name = (string)$row['TABLE_NAME'];
            $collation = $row['TABLE_COLLATION'] === null ? null : (string)$row['TABLE_COLLATION'];
            $charset = $collation === null ? null : (explode('_', $collation, 2)[0] ?: null);
            $options = trim((string)($row['CREATE_OPTIONS'] ?? ''));
            $observed['tables'][$name] = [
                'name'=>$name,
                'engine'=>$row['ENGINE'] === null ? null : (string)$row['ENGINE'],
                'charset'=>$charset,
                'collation'=>$collation,
                'options'=>$options === '' ? null : $options,
            ];
        }

        $createSqlByTable = [];
        foreach ($this->columnMetadata() as $row) {
            if (!isset($row['table'], $row['COLUMN_NAME'])) continue;
            $table = (string)$row['table'];
            $name = (string)$row['COLUMN_NAME'];
            $nullable = strtoupper((string)($row['IS_NULLABLE'] ?? 'NO')) === 'YES';
            $rawDefault = $row['COLUMN_DEFAULT'] ?? null;
            $defaultIsSqlNull = (bool)($row['COLUMN_DEFAULT_IS_NULL'] ?? false);
            if ($defaultIsSqlNull) {
                $default = null;
            } elseif ($nullable && is_string($rawDefault) && strtoupper(trim($rawDefault)) === 'NULL') {
                if (!array_key_exists($table, $createSqlByTable)) {
                    $quotedTable = '`'.str_replace('`', '``', $table).'`';
                    $definition = $this->pdo->query('SHOW CREATE TABLE '.$quotedTable)->fetch(PDO::FETCH_NUM);
                    $createSqlByTable[$table] = (string)($definition[1] ?? '');
                }
                $quotedColumn = '`'.str_replace('`', '``', $name).'`';
                $literalNullPattern = "~^\\s*".preg_quote($quotedColumn, "~")."\\s+.*?\\bDEFAULT\\s+'NULL'(?:\\s|,|$)~im";
                $default = preg_match($literalNullPattern, $createSqlByTable[$table]) === 1 ? 'NULL' : null;
            } else {
                $default = $rawDefault;
            }
            if (is_string($default) && strlen($default) >= 2 && $default[0] === "'" && substr($default, -1) === "'") {
                $default = str_replace(["''", "\\'", "\\\\"], ["'", "'", "\\"], substr($default, 1, -1));
            }
            $extra = strtolower((string)($row['EXTRA'] ?? ''));
            $generatedExpression = trim((string)($row['GENERATION_EXPRESSION'] ?? ''));
            $observed['columns'][$table][$name] = [
                'name'=>$name,
                'ordinal_position'=>(int)$row['ORDINAL_POSITION'],
                'type'=>strtolower((string)$row['COLUMN_TYPE']),
                'nullable'=>$nullable,
                'default'=>$default,
                'default_expression'=>null,
                'auto_increment'=>str_contains($extra, 'auto_increment'),
                'generated'=>$generatedExpression === '' ? null : $generatedExpression,
                'comment'=>null,
            ];
        }

        foreach ($this->indexMetadata() as $row) {
            if (!isset($row['table'], $row['INDEX_NAME'], $row['COLUMN_NAME'])) continue;
            $table = (string)$row['table'];
            $name = (string)$row['INDEX_NAME'];
            $observed['indexes'][$table][$name]['name'] = $name;
            $observed['indexes'][$table][$name]['columns'][] = [
                'name'=>(string)$row['COLUMN_NAME'],
                'sequence'=>(int)$row['SEQ_IN_INDEX'],
                'prefix_length'=>$row['SUB_PART'] === null ? null : (int)$row['SUB_PART'],
                'direction'=>strtoupper((string)($row['COLLATION'] ?? 'A')) === 'D' ? 'DESC' : 'ASC',
            ];
            $observed['indexes'][$table][$name]['unique'] = (int)$row['NON_UNIQUE'] === 0;
            $observed['indexes'][$table][$name]['type'] = strtoupper((string)$row['INDEX_TYPE']);
        }
        foreach ($observed['indexes'] as &$tableIndexes) {
            foreach ($tableIndexes as &$index) {
                usort($index['columns'], static fn(array $a, array $b): int => $a['sequence'] <=> $b['sequence']);
            }
            unset($index);
        }
        unset($tableIndexes);

        $foreignRows = $this->foreignKeys();
        $foreignKeyVisible = $this->absenceProof
            && !($foreignRows !== [] && isset($foreignRows[0]['state']) && $foreignRows[0]['state'] === 'UNKNOWN');
        foreach ($foreignRows as $row) {
            if (!isset($row['TABLE_NAME'], $row['CONSTRAINT_NAME'], $row['COLUMN_NAME'], $row['REFERENCED_TABLE_NAME'], $row['REFERENCED_COLUMN_NAME'])) continue;
            $table = (string)$row['TABLE_NAME'];
            $name = (string)$row['CONSTRAINT_NAME'];
            $observed['foreign_keys'][$table][$name]['name'] = $name;
            $observed['foreign_keys'][$table][$name]['source_columns'][] = (string)$row['COLUMN_NAME'];
            $observed['foreign_keys'][$table][$name]['referenced_table'] = (string)$row['REFERENCED_TABLE_NAME'];
            $observed['foreign_keys'][$table][$name]['referenced_columns'][] = (string)$row['REFERENCED_COLUMN_NAME'];
            $observed['foreign_keys'][$table][$name]['on_delete'] = strtoupper((string)$row['DELETE_RULE']);
            $observed['foreign_keys'][$table][$name]['on_update'] = strtoupper((string)$row['UPDATE_RULE']);
            $observed['foreign_keys'][$table][$name]['_sequence'][] = (int)($row['ORDINAL_POSITION'] ?? 1);
        }
        foreach ($observed['foreign_keys'] as &$tableKeys) {
            foreach ($tableKeys as &$foreignKey) {
                array_multisort($foreignKey['_sequence'], SORT_ASC, $foreignKey['source_columns'], $foreignKey['referenced_columns']);
                unset($foreignKey['_sequence']);
            }
            unset($foreignKey);
        }
        unset($tableKeys);

        $triggerStates = [];
        foreach ($this->triggerReconciliation() as $row) {
            if (isset($row['name'])) $triggerStates[$row['name']] = $row;
        }
        $manifestPath = dirname(__DIR__, 2).'/database/schema-contract/phase1d2b-final-schema-manifest.json';
        $manifest = is_file($manifestPath) ? json_decode((string)file_get_contents($manifestPath), true) : [];
        $expectedTriggerMap = [];
        foreach (($manifest['tables'] ?? []) as $table => $contract) {
            foreach (($contract['triggers'] ?? []) as $expectedTrigger) {
                $name = (string)($expectedTrigger['name'] ?? '');
                if ($name !== '') $expectedTriggerMap[$name] = ['table'=>$table,'contract'=>$expectedTrigger];
            }
        }
        $allTriggersVisible = $this->triggerAbsenceProof;
        try {
            $allTriggerRows = $this->pdo->query(
                'SELECT TRIGGER_NAME,EVENT_OBJECT_TABLE,EVENT_MANIPULATION,ACTION_TIMING,ACTION_ORIENTATION FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE()'
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($allTriggerRows as $row) {
                $name = (string)$row['TRIGGER_NAME'];
                $table = (string)$row['EVENT_OBJECT_TABLE'];
                $expected = $expectedTriggerMap[$name]['contract'] ?? null;
                $state = $triggerStates[$name]['state'] ?? 'UNEXPECTED';
                if (is_array($expected) && $state === 'PASS') {
                    $observed['triggers'][$table][$name] = $expected;
                } else {
                    $observed['triggers'][$table][$name] = [
                        'name'=>$name,
                        'timing'=>strtoupper((string)$row['ACTION_TIMING']),
                        'event'=>strtoupper((string)$row['EVENT_MANIPULATION']),
                        'orientation'=>strtoupper((string)$row['ACTION_ORIENTATION']),
                        'body_contract'=>is_array($expected) ? 'MISMATCH' : 'UNEXPECTED',
                    ];
                }
            }
        } catch (Throwable $e) {
            $allTriggersVisible = false;
        }

        return [
            'observed'=>$observed,
            'visibility'=>[
                'tables'=>$this->absenceProof,
                'columns'=>$this->absenceProof && $this->columnErrors === [],
                'indexes'=>$this->absenceProof && $this->indexErrors === [],
                'foreign_keys'=>$foreignKeyVisible,
                'triggers'=>$allTriggersVisible,
            ],
        ];
    }

    private function inventory(): void
    {
        $placeholders=implode(',',array_fill(0,count(self::TABLES),'?'));
        $inventory=$this->pdo->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE' AND TABLE_NAME IN ($placeholders)");
        $inventory->execute(self::TABLES);
        foreach($inventory->fetchAll(PDO::FETCH_COLUMN) as $t) $this->tables[(string)$t]=true;
        $this->absenceProof=$this->hasCompleteSchemaMetadataVisibility();
        $this->triggerAbsenceProof=$this->hasCompleteTriggerMetadataVisibility();
        foreach(self::TABLES as $t) {
            $this->columns[$t]=[];
            if(!isset($this->tables[$t])) continue;
            try {
                $q=$this->pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
                $q->execute([$t]);
                foreach($q->fetchAll(PDO::FETCH_COLUMN) as $c) $this->columns[$t][(string)$c]=true;
            } catch(Throwable $e) { $this->columnErrors[$t]=$this->safeError($e); }
        }
    }

    private function tableMetadata(): array
    {
        $out=[];
        foreach(self::TABLES as $t) {
            if(!isset($this->tables[$t])) {$out[]=['table'=>$t,'state'=>phase1cMetadataAbsenceState($this->absenceProof),'reason'=>$this->absenceProof?'TABLE_ABSENT':'METADATA_VISIBILITY_NOT_PROVEN'];continue;}
            try {
                $q=$this->pdo->prepare("SELECT ENGINE,ROW_FORMAT,TABLE_COLLATION,CREATE_OPTIONS FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND TABLE_TYPE='BASE TABLE'");
                $q->execute([$t]); $options=$q->fetch()?:[];
                $create=$this->pdo->query('SHOW CREATE TABLE '.$this->identifier($t))->fetch(PDO::FETCH_NUM);
                $out[]=['table'=>$t,'state'=>'PASS','options'=>$options,'create_table_sql'=>$this->sanitizeCreateSql((string)($create[1]??''))];
            } catch(Throwable $e) {$out[]=['table'=>$t,'state'=>'UNKNOWN','error'=>$this->safeError($e)];}
        }
        return $out;
    }

    private function columnMetadata(): array
    {
        $out=[];
        foreach(self::TABLES as $t) {
            if(!isset($this->tables[$t])) continue;
            try {
                $q=$this->pdo->prepare('SELECT COLUMN_NAME,ORDINAL_POSITION,COLUMN_DEFAULT,COLUMN_DEFAULT IS NULL AS COLUMN_DEFAULT_IS_NULL,IS_NULLABLE,DATA_TYPE,COLUMN_TYPE,CHARACTER_MAXIMUM_LENGTH,CHARACTER_OCTET_LENGTH,NUMERIC_PRECISION,NUMERIC_SCALE,DATETIME_PRECISION,CHARACTER_SET_NAME,COLLATION_NAME,COLUMN_KEY,EXTRA,PRIVILEGES,COLUMN_COMMENT,GENERATION_EXPRESSION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');
                $q->execute([$t]);
                foreach($q->fetchAll() as $r) { $r['COLUMN_DEFAULT']=$this->safeDefault((string)$r['COLUMN_NAME'],$r['COLUMN_DEFAULT']); unset($r['PRIVILEGES'],$r['COLUMN_COMMENT']); $out[]=['table'=>$t]+$r; }
            } catch(Throwable $e) {$this->columnErrors[$t]=$this->safeError($e);$out[]=['table'=>$t,'state'=>'UNKNOWN','error'=>$this->safeError($e)];}
        }
        return $out;
    }

    private function indexMetadata(): array
    {
        $out=[];
        foreach(self::TABLES as $t) {
            if(!isset($this->tables[$t])) continue;
            try {
                $q=$this->pdo->prepare('SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,COLLATION,SUB_PART,NULLABLE,INDEX_TYPE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY INDEX_NAME,SEQ_IN_INDEX');
                $q->execute([$t]);
                foreach($q->fetchAll() as $r) $out[]=['table'=>$t]+$r;
            } catch(Throwable $e) {$this->indexErrors[$t]=$this->safeError($e);$out[]=['table'=>$t,'state'=>'UNKNOWN','error'=>$this->safeError($e)];}
        }
        return $out;
    }

    private function indexReconciliation(): array
    {
        $actual=[];
        foreach($this->indexMetadata() as $r) if(isset($r['INDEX_NAME'])) $actual[$r['table']][$r['INDEX_NAME']][]=$r;
        $out=[];
        foreach(self::INDEXES as [$table,$name,$columns,$unique]) {
            $expected=['table'=>$table,'name'=>$name,'columns'=>$columns,'unique'=>$unique,'index_type'=>'BTREE'];
            if(!isset($this->tables[$table])) {$out[]=$expected+['state'=>$this->absenceProof?'MISSING':'UNKNOWN','reason'=>$this->absenceProof?'TABLE_MISSING':'METADATA_VISIBILITY_NOT_PROVEN'];continue;}
            if(isset($this->indexErrors[$table])) {$out[]=$expected+['state'=>'UNKNOWN','reason'=>'INDEX_METADATA_UNAVAILABLE','error'=>$this->indexErrors[$table]];continue;}
            if(isset($this->columnErrors[$table])) {$out[]=$expected+['state'=>'UNKNOWN','reason'=>'COLUMN_METADATA_UNAVAILABLE','error'=>$this->columnErrors[$table]];continue;}
            $rows=$actual[$table][$name]??[];
            if(!$rows) {$out[]=$expected+['state'=>$this->absenceProof?'MISSING':'UNKNOWN','reason'=>$this->absenceProof?'INDEX_ABSENT':'METADATA_VISIBILITY_NOT_PROVEN'];continue;}
            usort($rows,fn($a,$b)=>(int)$a['SEQ_IN_INDEX']<=>(int)$b['SEQ_IN_INDEX']);
            $cols=array_map(fn($r)=>(string)$r['COLUMN_NAME'],$rows);
            $isUnique=(int)$rows[0]['NON_UNIQUE']===0;
            $type=strtoupper((string)$rows[0]['INDEX_TYPE']);
            $ok=$cols===$columns && $isUnique===$unique && $type==='BTREE' && count(array_filter($rows,fn($r)=>$r['SUB_PART']!==null))===0;
            $out[]=$expected+['state'=>$ok?'PASS':'FAIL','observed'=>['columns'=>$cols,'unique'=>$isUnique,'type'=>$type,'prefix_lengths'=>array_column($rows,'SUB_PART')]];
        }
        return $out;
    }

    private function columnReconciliation(): array
    {
        $out=[];
        $query='SELECT COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,COLUMN_DEFAULT IS NULL AS COLUMN_DEFAULT_IS_NULL FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?';

        foreach(self::EXPECTED_COLUMNS as $contract) {
            [$table,$column,$expectedType,$expectedNullable]=$contract;
            $expected=['table'=>$table,'column'=>$column,'type'=>$expectedType,'nullable'=>$expectedNullable];
            $hasExpectedDefault=array_key_exists(4,$contract);
            if($hasExpectedDefault) $expected['default']=$contract[4];

            if(!isset($this->tables[$table])) {
                $out[]=$expected+['state'=>$this->absenceProof?'MISSING':'UNKNOWN','reason'=>$this->absenceProof?'TABLE_ABSENT':'METADATA_VISIBILITY_NOT_PROVEN'];
                continue;
            }
            if(isset($this->columnErrors[$table])) {
                $out[]=$expected+['state'=>'UNKNOWN','reason'=>'COLUMN_METADATA_UNAVAILABLE','error'=>$this->columnErrors[$table]];
                continue;
            }

            try {
                $statement=$this->pdo->prepare($query);
                $statement->execute([$table,$column]);
                $actual=$statement->fetch(PDO::FETCH_ASSOC);
            } catch(Throwable $e) {
                $out[]=$expected+['state'=>'UNKNOWN','reason'=>'COLUMN_METADATA_UNAVAILABLE','error'=>$this->safeError($e)];
                continue;
            }

            if(!is_array($actual)) {
                $out[]=$expected+['state'=>$this->absenceProof?'MISSING':'UNKNOWN','reason'=>$this->absenceProof?'COLUMN_ABSENT':'METADATA_VISIBILITY_NOT_PROVEN'];
                continue;
            }
            if(!array_key_exists('COLUMN_TYPE',$actual)||!array_key_exists('IS_NULLABLE',$actual)||!array_key_exists('COLUMN_DEFAULT_IS_NULL',$actual)) {
                $out[]=$expected+['state'=>'UNKNOWN','reason'=>'COLUMN_DEFINITION_METADATA_INCOMPLETE'];
                continue;
            }

            $actualType=(string)$actual['COLUMN_TYPE'];
            $actualNullable=$actual['IS_NULLABLE']==='YES';
            $matches=$this->normalizeMysqlColumnType($actualType)===$this->normalizeMysqlColumnType($expectedType)
                && $actualNullable===$expectedNullable;
            $observed=['type'=>$actualType,'nullable'=>$actualNullable];

            if($hasExpectedDefault) {
                $actualDefaultIsNull=$this->mysqlColumnDefaultIsSqlNull($table,$column,$actual,$actualNullable);
                if($actualDefaultIsNull===null) {
                    $definition=['state'=>$matches?'UNKNOWN':'FAIL','observed'=>$observed];
                    if($matches) $definition['reason']='COLUMN_DEFAULT_METADATA_AMBIGUOUS';
                    $out[]=$expected+$definition;
                    continue;
                }

                if($contract[4]===null) {
                    $matches=$matches&&$actualDefaultIsNull;
                    $observed['default_is_null']=$actualDefaultIsNull;
                } else {
                    $actualDefault=$actualDefaultIsNull?null:$this->normalizeMysqlDefault($actual['COLUMN_DEFAULT']??null);
                    $matches=$matches&&$actualDefault===$this->normalizeMysqlDefault($contract[4]);
                    $observed['default']=$actualDefault;
                }
            }

            $out[]=$expected+['state'=>$matches?'PASS':'FAIL','observed'=>$observed];
        }

        return $out;
    }

    private function mysqlColumnDefaultIsSqlNull(string $table,string $column,array $actual,bool $nullable): ?bool
    {
        $rawDefault=$actual['COLUMN_DEFAULT']??null;
        if($rawDefault===null) return true;

        if($nullable&&is_string($rawDefault)&&strtoupper(trim($rawDefault))==='NULL') {
            try {
                $create=$this->pdo->query('SHOW CREATE TABLE '.$this->identifier($table))->fetch(PDO::FETCH_NUM);
                $createSql=(string)($create[1]??'');
                $wrappedColumn=preg_quote($this->identifier($column),'/');
                if(preg_match('/^\\s*'.$wrappedColumn.'\\s+([^\\n]*)$/m',$createSql,$definition)!==1) return null;

                return phase1cMysqlNullDefaultFromDefinition($definition[1]);
            } catch(Throwable $e) {
                return null;
            }
        }

        if(!array_key_exists('COLUMN_DEFAULT_IS_NULL',$actual)) return null;
        if((bool)$actual['COLUMN_DEFAULT_IS_NULL']) return null;

        return false;
    }

    private function foreignKeyReconciliation(): array
    {
        $out=[];
        $sql='SELECT k.CONSTRAINT_NAME,k.TABLE_NAME,k.COLUMN_NAME,k.ORDINAL_POSITION,k.POSITION_IN_UNIQUE_CONSTRAINT,k.REFERENCED_TABLE_SCHEMA,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.MATCH_OPTION,r.UPDATE_RULE,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME AND r.TABLE_NAME=k.TABLE_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.TABLE_NAME=? AND k.CONSTRAINT_NAME=? AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY k.ORDINAL_POSITION';

        foreach(self::EXPECTED_FOREIGN_KEYS as [$name,$table,$column,$parent,$parentColumn,$deleteRule]) {
            $expected=['name'=>$name,'table'=>$table,'column'=>$column,'referenced_table'=>$parent,'referenced_column'=>$parentColumn,'match_option'=>'NONE','update_rule'=>'RESTRICT','delete_rule'=>$deleteRule];
            if(!isset($this->tables[$table])||!isset($this->tables[$parent])) {
                $out[]=$expected+['state'=>$this->absenceProof?'MISSING':'UNKNOWN','reason'=>$this->absenceProof?'PREREQUISITE_TABLE_ABSENT':'METADATA_VISIBILITY_NOT_PROVEN'];
                continue;
            }
            if(isset($this->columnErrors[$table])||isset($this->columnErrors[$parent])) {
                $out[]=$expected+['state'=>'UNKNOWN','reason'=>'COLUMN_METADATA_UNAVAILABLE'];
                continue;
            }

            try {
                $statement=$this->pdo->prepare($sql);
                $statement->execute([$table,$name]);
                $rows=$statement->fetchAll(PDO::FETCH_ASSOC);
            } catch(Throwable $e) {
                $out[]=$expected+['state'=>'UNKNOWN','reason'=>'FOREIGN_KEY_METADATA_UNAVAILABLE','error'=>$this->safeError($e)];
                continue;
            }

            if($rows===[]) {
                $out[]=$expected+['state'=>$this->absenceProof?'MISSING':'UNKNOWN','reason'=>$this->absenceProof?'FOREIGN_KEY_ABSENT':'METADATA_VISIBILITY_NOT_PROVEN'];
                continue;
            }

            $row=$rows[0];
            $required=['CONSTRAINT_NAME','TABLE_NAME','COLUMN_NAME','ORDINAL_POSITION','POSITION_IN_UNIQUE_CONSTRAINT','REFERENCED_TABLE_SCHEMA','REFERENCED_TABLE_NAME','REFERENCED_COLUMN_NAME','MATCH_OPTION','UPDATE_RULE','DELETE_RULE'];
            if(count($rows)!==1||array_diff($required,array_keys($row))!==[]) {
                $out[]=$expected+['state'=>'UNKNOWN','reason'=>'FOREIGN_KEY_DEFINITION_METADATA_INCOMPLETE'];
                continue;
            }

            $observed=[
                'name'=>(string)$row['CONSTRAINT_NAME'],
                'table'=>(string)$row['TABLE_NAME'],
                'column'=>(string)$row['COLUMN_NAME'],
                'ordinal_position'=>(int)$row['ORDINAL_POSITION'],
                'position_in_unique_constraint'=>(int)$row['POSITION_IN_UNIQUE_CONSTRAINT'],
                'referenced_table_schema'=>(string)$row['REFERENCED_TABLE_SCHEMA'],
                'referenced_table'=>(string)$row['REFERENCED_TABLE_NAME'],
                'referenced_column'=>(string)$row['REFERENCED_COLUMN_NAME'],
                'match_option'=>strtoupper((string)$row['MATCH_OPTION']),
                'update_rule'=>strtoupper((string)$row['UPDATE_RULE']),
                'delete_rule'=>strtoupper((string)$row['DELETE_RULE']),
            ];
            $matches=$observed['name']===$name
                && $observed['table']===$table
                && $observed['column']===$column
                && $observed['ordinal_position']===1
                && $observed['position_in_unique_constraint']===1
                && $observed['referenced_table_schema']===$this->databaseName
                && $observed['referenced_table']===$parent
                && $observed['referenced_column']===$parentColumn
                && $observed['match_option']==='NONE'
                && $this->referentialActionMatches($observed['update_rule'],'RESTRICT')
                && $this->referentialActionMatches($observed['delete_rule'],$deleteRule);
            $out[]=$expected+['state'=>$matches?'PASS':'FAIL','observed'=>$observed];
        }

        return $out;
    }

    private function triggerReconciliation(): array
    {
        $out=[];
        $sql='SELECT TRIGGER_NAME,EVENT_OBJECT_TABLE,EVENT_MANIPULATION,ACTION_TIMING,ACTION_ORIENTATION,ACTION_STATEMENT FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME=?';

        foreach($this->expectedTriggers() as $contract) {
            $expected=['name'=>$contract['name'],'table'=>$contract['table'],'event'=>$contract['event'],'timing'=>'BEFORE','orientation'=>'ROW','body_contract'=>$contract['body_contract']];
            if(!isset($this->tables[$contract['table']])) {
                $out[]=$expected+['state'=>$this->absenceProof?'MISSING':'UNKNOWN','reason'=>$this->absenceProof?'TABLE_ABSENT':'METADATA_VISIBILITY_NOT_PROVEN'];
                continue;
            }

            try {
                $statement=$this->pdo->prepare($sql);
                $statement->execute([$contract['name']]);
                $rows=$statement->fetchAll(PDO::FETCH_ASSOC);
            } catch(Throwable $e) {
                $out[]=$expected+['state'=>'UNKNOWN','reason'=>'TRIGGER_METADATA_UNAVAILABLE','error'=>$this->safeError($e)];
                continue;
            }

            if($rows===[]) {
                $out[]=$expected+['state'=>$this->triggerAbsenceProof?'MISSING':'UNKNOWN','reason'=>$this->triggerAbsenceProof?'TRIGGER_ABSENT':'TRIGGER_METADATA_VISIBILITY_NOT_PROVEN'];
                continue;
            }
            if(count($rows)!==1||!isset($rows[0]['EVENT_OBJECT_TABLE'],$rows[0]['EVENT_MANIPULATION'],$rows[0]['ACTION_TIMING'],$rows[0]['ACTION_ORIENTATION'],$rows[0]['ACTION_STATEMENT'])) {
                $out[]=$expected+['state'=>'UNKNOWN','reason'=>'TRIGGER_DEFINITION_METADATA_INCOMPLETE'];
                continue;
            }

            $actual=$rows[0];
            $statement=$this->normalizeSql((string)$actual['ACTION_STATEMENT']);
            $observed=[
                'table'=>(string)$actual['EVENT_OBJECT_TABLE'],
                'event'=>strtoupper((string)$actual['EVENT_MANIPULATION']),
                'timing'=>strtoupper((string)$actual['ACTION_TIMING']),
                'orientation'=>strtoupper((string)$actual['ACTION_ORIENTATION']),
                'body_sha256'=>hash('sha256',$statement),
            ];
            $matches=$observed['table']===$contract['table']
                && $observed['event']===$contract['event']
                && $observed['timing']==='BEFORE'
                && $observed['orientation']==='ROW'
                && $this->triggerBodyMatches($statement,$contract['body_contract']);
            $out[]=$expected+['state'=>$matches?'PASS':'FAIL','observed'=>$observed];
        }

        return $out;
    }

    private function expectedTriggers(): array
    {
        $triggers=[];
        foreach(self::AUDIT_TRIGGER_TABLES as $table) {
            $suffix=substr(hash('sha256',$table),0,16);
            $bodyContract=$table==='import_reconciliation_events'?'reconciliation_detach':'append_only';
            $triggers[]=['name'=>'audit_immut_'.$suffix.'_u','table'=>$table,'event'=>'UPDATE','body_contract'=>$bodyContract];
            $triggers[]=['name'=>'audit_immut_'.$suffix.'_d','table'=>$table,'event'=>'DELETE','body_contract'=>'append_only'];
        }
        $triggers[]=['name'=>'identity_decision_update_guard','table'=>'identity_resolution_decisions','event'=>'UPDATE','body_contract'=>'identity_key_enrichment'];
        $triggers[]=['name'=>'identity_decision_delete_guard','table'=>'identity_resolution_decisions','event'=>'DELETE','body_contract'=>'identity_decision_delete'];

        return $triggers;
    }

    private function triggerBodyMatches(string $statement, string $contract): bool
    {
        if($contract==='append_only') {
            return $statement==="signal sqlstate '45000' set message_text = 'append-only audit history cannot be changed or deleted'";
        }
        if($contract==='identity_decision_delete') {
            return $statement==="signal sqlstate '45000' set message_text = 'identity decision history cannot be deleted'";
        }

        $conditions=match($contract) {
            'reconciliation_detach'=>[
                'OLD.import_row_id IS NOT NULL','NEW.import_row_id IS NULL','OLD.id <=> NEW.id',
                'OLD.import_batch_id <=> NEW.import_batch_id','OLD.event_type <=> NEW.event_type',
                'OLD.state <=> NEW.state','OLD.severity <=> NEW.severity','OLD.subject_type <=> NEW.subject_type',
                'OLD.subject_id <=> NEW.subject_id','OLD.description <=> NEW.description','OLD.metadata <=> NEW.metadata',
                'OLD.actor_type <=> NEW.actor_type','OLD.actor_id <=> NEW.actor_id','OLD.occurred_at <=> NEW.occurred_at',
                'OLD.created_at <=> NEW.created_at','OLD.updated_at <=> NEW.updated_at',
            ],
            'identity_key_enrichment'=>[
                'OLD.decision_identity_key IS NULL','NEW.decision_identity_key IS NOT NULL','OLD.id <=> NEW.id',
                'OLD.import_row_id <=> NEW.import_row_id','OLD.institution_id <=> NEW.institution_id',
                'OLD.decision <=> NEW.decision','OLD.actor_type <=> NEW.actor_type','OLD.actor_id <=> NEW.actor_id',
                'OLD.reason <=> NEW.reason','OLD.decided_at <=> NEW.decided_at','OLD.created_at <=> NEW.created_at',
            ],
            default=>[],
        };
        if($conditions===[]) return false;

        $expectedMessage=$contract==='reconciliation_detach'
            ? 'import reconciliation history is immutable except nullable row detachment'
            : 'identity decision history is immutable except one-time key enrichment';
        if (!preg_match("/if\\s+not\\s*\\((.*?)\\)\\s*then\\s*signal\\s+sqlstate\\s+'45000'\\s+set\\s+message_text\\s*=\\s*'([^']*)';\\s*end\\s+if;\\s*end$/s", $statement, $matches)) {
            return false;
        }
        if (strtolower(trim($matches[2])) !== $expectedMessage) {
            return false;
        }
        $actualConditions = array_map(
            fn(string $condition): string => $this->normalizeSql($condition),
            explode(' and ', $matches[1])
        );
        $expectedConditions = array_map(
            fn(string $condition): string => $this->normalizeSql($condition),
            $conditions
        );
        sort($actualConditions, SORT_STRING);
        sort($expectedConditions, SORT_STRING);

        return $actualConditions === $expectedConditions;
    }

    private function migrationObjectReconciliation(array $ledgerReconciliation): array
    {
        $indexRows = [];
        foreach ($this->indexReconciliation() as $row) {
            $indexRows[$row['table'].'.'.$row['name']] = $row['state'];
        }
        $foreignKeyRows = [];
        foreach ($this->foreignKeyReconciliation() as $row) {
            $foreignKeyRows[$row['name']] = $row['state'];
        }
        $triggerRows = [];
        foreach ($this->triggerReconciliation() as $row) {
            $triggerRows[$row['name']] = $row['state'];
        }
        $columnRows = [];
        foreach (self::EXPECTED_COLUMNS as $contract) {
            [$table, $column] = $contract;
            if (!isset($this->tables[$table])) {
                $state = $this->absenceProof ? 'MISSING' : 'UNKNOWN';
            } elseif (isset($this->columnErrors[$table])) {
                $state = 'UNKNOWN';
            } else {
                $state = isset($this->columns[$table][$column]) ? 'PASS' : ($this->absenceProof ? 'MISSING' : 'UNKNOWN');
            }
            $columnRows[$table.'.'.$column] = $state;
        }

        $triggersByMigration = [];
        foreach ($this->expectedTriggers() as $trigger) {
            $triggersByMigration[] = $trigger['name'];
        }
        $requirements = [
            self::EXPECTED_MIGRATIONS[0] => [
                'tables' => ['password_reset_tokens'],
                'columns' => ['password_reset_tokens.email','password_reset_tokens.token','password_reset_tokens.created_at'],
                'indexes' => ['password_reset_tokens.PRIMARY'],
            ],
            self::EXPECTED_MIGRATIONS[1] => ['columns' => ['users.must_change_password']],
            self::EXPECTED_MIGRATIONS[2] => ['columns' => ['import_batches.duplicate_rows']],
            self::EXPECTED_MIGRATIONS[3] => [
                'tables' => ['structured_enrichment_promotion_events'],
                'columns' => ['structured_enrichment_promotion_events.enrichment_import_row_id','structured_enrichment_promotion_events.import_batch_id','structured_enrichment_promotion_events.occurred_at'],
            ],
            self::EXPECTED_MIGRATIONS[4] => [
                'tables' => ['governed_execution_leases'],
                'columns' => ['governed_execution_leases.task_key','governed_execution_leases.owner_token','governed_execution_leases.run_id','governed_execution_leases.expires_at'],
                'indexes' => ['governed_execution_leases.governed_execution_leases_task_key_unique','governed_execution_leases.governed_execution_leases_expires_at_index'],
            ],
            self::EXPECTED_MIGRATIONS[5] => [
                'tables' => ['governed_execution_runs','governed_execution_run_events'],
                'columns' => ['governed_execution_runs.id','governed_execution_run_events.run_id','governed_execution_run_events.event_type','governed_execution_run_events.occurred_at'],
            ],
            self::EXPECTED_MIGRATIONS[6] => [
                'columns' => ['source_retrieval_runs.idempotency_key'],
                'indexes' => ['source_retrieval_runs.source_retrieval_runs_idempotency_key_unique'],
            ],
            self::EXPECTED_MIGRATIONS[7] => [
                'columns' => ['source_snapshots.capture_key','source_change_observations.current_snapshot_id'],
                'indexes' => ['source_snapshots.source_snapshots_capture_key_unique','source_change_observations.source_observations_current_snapshot_unique'],
            ],
            self::EXPECTED_MIGRATIONS[8] => [
                'columns' => ['institute_discovery_run_outcomes.idempotency_key'],
                'indexes' => ['institute_discovery_run_outcomes.institute_discovery_run_outcomes_idempotency_key_unique'],
            ],
            self::EXPECTED_MIGRATIONS[9] => [
                'columns' => ['change_events.source_change_assertion_link_id'],
                'indexes' => ['change_events.change_events_source_link_unique'],
            ],
            self::EXPECTED_MIGRATIONS[10] => [
                'tables' => ['data_reverification_queue_identities'],
                'columns' => ['data_reverification_queue_identities.identity_key','data_reverification_queue_identities.subject_type','data_reverification_queue_identities.subject_id','data_reverification_queue_identities.active_queue_id'],
            ],
            self::EXPECTED_MIGRATIONS[11] => [
                'tables' => ['monitoring_alert_identities'],
                'columns' => ['monitoring_alert_identities.dedupe_key','monitoring_alert_identities.active_alert_id','security_audit_events.monitoring_idempotency_key'],
                'indexes' => ['security_audit_events.security_audit_events_monitoring_idempotency_key_unique'],
            ],
            self::EXPECTED_MIGRATIONS[12] => [
                'tables' => ['monitoring_policy_runs','monitoring_policy_run_sources'],
                'columns' => ['monitoring_policy_runs.source_monitoring_policy_id','monitoring_policy_runs.execution_run_id','monitoring_policy_runs.started_at','monitoring_policy_run_sources.monitoring_policy_run_id','monitoring_policy_run_sources.source_id','monitoring_policy_run_sources.retrieval_run_id','monitoring_policy_run_sources.observation_id'],
                'indexes' => ['monitoring_policy_run_sources.monitoring_policy_run_source_unique','monitoring_policy_run_sources.monitoring_policy_source_status_idx'],
            ],
            self::EXPECTED_MIGRATIONS[13] => [
                'columns' => ['import_batches.discovery_identity_key','evidence.discovery_identity_key','import_rows.discovery_identity_key','assertions.discovery_identity_key'],
                'indexes' => ['import_batches.import_batches_discovery_identity_unique','evidence.evidence_discovery_identity_unique','import_rows.import_rows_discovery_identity_unique','assertions.assertions_discovery_identity_unique'],
            ],
            self::EXPECTED_MIGRATIONS[14] => [
                'tables' => ['import_conflict_events'],
                'columns' => ['identity_match_candidates.identity_key','identity_resolution_decisions.decision_identity_key','import_conflicts.identity_key','import_conflict_events.idempotency_key'],
                'indexes' => ['identity_match_candidates.identity_match_candidates_identity_key_unique','identity_resolution_decisions.identity_resolution_decisions_identity_key_unique','import_conflicts.import_conflicts_identity_key_unique','import_conflict_events.import_conflict_events_idempotency_key_unique'],
            ],
            self::EXPECTED_MIGRATIONS[15] => [
                'tables' => ['source_snapshot_reconciliation_requests'],
                'columns' => ['source_snapshot_reconciliation_requests.identity_key','source_snapshot_reconciliation_requests.source_id','source_snapshot_reconciliation_requests.triggering_snapshot_id','source_snapshot_reconciliation_requests.current_snapshot_id','source_snapshot_reconciliation_requests.expected_previous_snapshot_id','source_snapshot_reconciliation_requests.recorded_previous_snapshot_id'],
                'indexes' => ['source_snapshot_reconciliation_requests.source_snapshot_reconciliation_requests_identity_key_unique','source_snapshot_reconciliation_requests.snapshot_reconciliation_source_status_idx','source_snapshot_reconciliation_requests.snapshot_reconciliation_current_status_idx'],
                'foreign_keys' => ['ssr_source_fk','ssr_triggering_snapshot_fk','ssr_current_snapshot_fk','ssr_expected_previous_fk','ssr_recorded_previous_fk'],
            ],
            self::EXPECTED_MIGRATIONS[16] => [
                'foreign_keys' => ['identity_decision_row_restrict_fk','verification_decision_assertion_restrict_fk','import_reconciliation_batch_restrict_fk','import_promotion_decision_row_restrict_fk','import_promotion_event_row_restrict_fk','import_promotion_event_batch_restrict_fk','reverification_event_queue_restrict_fk','structured_enrichment_row_restrict_fk','structured_enrichment_batch_restrict_fk','import_conflict_event_restrict_fk','governed_run_event_restrict_fk'],
                'triggers' => $triggersByMigration,
            ],
            self::EXPECTED_MIGRATIONS[17] => [
                'columns' => ['monitoring_policy_runs.heartbeat_at'],
                'foreign_keys' => ['mp_run_policy_fk','mp_run_exec_fk','mp_source_run_fk','mp_source_source_fk','mp_source_retrieval_fk','mp_source_observation_fk','mai_active_alert_fk'],
            ],
        ];

        $ledgerByName = [];
        foreach ($ledgerReconciliation as $entry) {
            $ledgerByName[$entry['migration']] = $entry['state'];
        }

        $out = [];
        foreach (self::EXPECTED_MIGRATIONS as $migration) {
            $required = $requirements[$migration] ?? [];
            $objects = [];
            foreach ($required['tables'] ?? [] as $table) {
                $state = isset($this->tables[$table]) ? 'PASS' : ($this->absenceProof ? 'MISSING' : 'UNKNOWN');
                $objects[] = ['object' => 'table:'.$table, 'state' => $state];
            }
            foreach ($required['columns'] ?? [] as $column) {
                $objects[] = ['object' => 'column:'.$column, 'state' => $columnRows[$column] ?? 'UNKNOWN'];
            }
            foreach ($required['indexes'] ?? [] as $index) {
                $objects[] = ['object' => 'index:'.$index, 'state' => $indexRows[$index] ?? 'UNKNOWN'];
            }
            foreach ($required['foreign_keys'] ?? [] as $foreignKey) {
                $objects[] = ['object' => 'foreign_key:'.$foreignKey, 'state' => $foreignKeyRows[$foreignKey] ?? 'UNKNOWN'];
            }
            foreach ($required['triggers'] ?? [] as $trigger) {
                $objects[] = ['object' => 'trigger:'.$trigger, 'state' => $triggerRows[$trigger] ?? 'UNKNOWN'];
            }

            $states = array_column($objects, 'state');
            $presentCount = count(array_filter($states, static fn (string $state): bool => $state === 'PASS'));
            $missingCount = count(array_filter($states, static fn (string $state): bool => $state === 'MISSING'));
            $unknownCount = count(array_filter($states, static fn (string $state): bool => $state === 'UNKNOWN'));
            $failCount = count(array_filter($states, static fn (string $state): bool => $state === 'FAIL'));
            if ($failCount > 0 || ($presentCount > 0 && $missingCount > 0)) {
                $objectState = 'FAIL';
            } elseif ($unknownCount > 0) {
                $objectState = 'UNKNOWN';
            } elseif ($missingCount > 0) {
                $objectState = 'MISSING';
            } else {
                $objectState = 'PASS';
            }

            $ledgerState = $ledgerByName[$migration] ?? 'UNKNOWN';
            $consistency = phase1cClassifyMigrationObjectLedgerState($ledgerState, $objectState);
            $out[] = [
                'migration' => $migration,
                'ledger_state' => $ledgerState,
                'object_state' => $objectState,
                'consistency_state' => $consistency['state'],
                'reason' => $consistency['reason'],
                'objects' => $objects,
            ];
        }

        return $out;
    }

    private function fullMigrationLedgerInventory(): array
    {
        if (!isset($this->tables['migrations'])) {
            return [
                'state' => $this->absenceProof ? 'MISSING' : 'UNKNOWN',
                'reason' => $this->absenceProof ? 'LEDGER_TABLE_ABSENT' : 'METADATA_VISIBILITY_NOT_PROVEN',
                'source_file_count' => null,
                'ledger_row_count' => null,
                'distinct_ledger_migration_count' => null,
                'source_files_not_recorded' => [],
                'ledger_entries_without_source_files' => [],
                'duplicate_ledger_entries' => [],
                'invalid_ledger_rows' => [],
                'batch_numbers' => [],
                'batch_gaps' => [],
                'batch_gap_state' => 'UNKNOWN',
                'ordering_state' => 'UNKNOWN',
                'ordering_anomalies' => [],
            ];
        }
        if (isset($this->columnErrors['migrations']) || !$this->has('migrations', ['migration', 'batch'])) {
            return [
                'state' => 'UNKNOWN',
                'reason' => 'LEDGER_COLUMNS_OR_METADATA_UNAVAILABLE',
                'source_file_count' => null,
                'ledger_row_count' => null,
                'distinct_ledger_migration_count' => null,
                'source_files_not_recorded' => [],
                'ledger_entries_without_source_files' => [],
                'duplicate_ledger_entries' => [],
                'invalid_ledger_rows' => [],
                'batch_numbers' => [],
                'batch_gaps' => [],
                'batch_gap_state' => 'UNKNOWN',
                'ordering_state' => 'UNKNOWN',
                'ordering_anomalies' => [],
            ];
        }

        try {
            $migrationDirectory = dirname(__DIR__, 2).'/database/migrations';
            if (!is_dir($migrationDirectory) || !is_readable($migrationDirectory)) {
                return [
                    'state' => 'UNKNOWN',
                    'reason' => 'MIGRATION_SOURCE_DIRECTORY_UNAVAILABLE',
                    'source_file_count' => null,
                    'ledger_row_count' => null,
                    'distinct_ledger_migration_count' => null,
                    'source_files_not_recorded' => [],
                    'ledger_entries_without_source_files' => [],
                    'duplicate_ledger_entries' => [],
                    'invalid_ledger_rows' => [],
                    'batch_numbers' => [],
                    'batch_gaps' => [],
                    'batch_gap_state' => 'UNKNOWN',
                    'ordering_state' => 'UNKNOWN',
                    'ordering_anomalies' => [],
                ];
            }
            $sourcePaths = glob($migrationDirectory.'/*.php');
            if ($sourcePaths === false) {
                throw new RuntimeException('Migration source filename inventory is unavailable.');
            }
            $sourceNames = array_map(static fn (string $path): string => basename($path, '.php'), $sourcePaths);
            $hasId = isset($this->columns['migrations']['id']);
            $select = $hasId ? 'SELECT id,migration,batch FROM migrations ORDER BY id' : 'SELECT migration,batch FROM migrations ORDER BY batch,migration';
            $rows = $this->pdo->query($select)->fetchAll(PDO::FETCH_ASSOC);
            $analysis = phase1cAnalyzeFullMigrationLedger($sourceNames, $rows);
            if (!$hasId && $rows !== []) {
                $analysis['ordering_state'] = 'UNKNOWN';
            }
            return $analysis;
        } catch (Throwable $e) {
            return [
                'state' => 'UNKNOWN',
                'reason' => 'FULL_LEDGER_INVENTORY_UNAVAILABLE',
                'error' => $this->safeError($e),
                'source_file_count' => null,
                'ledger_row_count' => null,
                'distinct_ledger_migration_count' => null,
                'source_files_not_recorded' => [],
                'ledger_entries_without_source_files' => [],
                'duplicate_ledger_entries' => [],
                'invalid_ledger_rows' => [],
                'batch_numbers' => [],
                'batch_gaps' => [],
                'batch_gap_state' => 'UNKNOWN',
                'ordering_state' => 'UNKNOWN',
                'ordering_anomalies' => [],
            ];
        }
    }

    private function migrationLedgerReconciliation(): array
    {
        $out=[];
        if(!isset($this->tables['migrations'])) {
            $state=$this->absenceProof?'MISSING':'UNKNOWN';
            foreach(self::EXPECTED_MIGRATIONS as $migration) $out[]=['migration'=>$migration,'state'=>$state,'reason'=>$this->absenceProof?'LEDGER_TABLE_ABSENT':'METADATA_VISIBILITY_NOT_PROVEN','record_count'=>0,'duplicate_count'=>0,'batches'=>[]];
            return $out;
        }
        if(isset($this->columnErrors['migrations'])) {
            foreach(self::EXPECTED_MIGRATIONS as $migration) $out[]=['migration'=>$migration,'state'=>'UNKNOWN','reason'=>'LEDGER_COLUMN_METADATA_UNAVAILABLE','record_count'=>null,'duplicate_count'=>null,'batches'=>[]];
            return $out;
        }
        if(!$this->has('migrations',['migration','batch'])) {
            $state=$this->absenceProof?'MISSING':'UNKNOWN';
            foreach(self::EXPECTED_MIGRATIONS as $migration) $out[]=['migration'=>$migration,'state'=>$state,'reason'=>$this->absenceProof?'LEDGER_COLUMNS_MISSING':'METADATA_VISIBILITY_NOT_PROVEN','record_count'=>0,'duplicate_count'=>0,'batches'=>[]];
            return $out;
        }

        $placeholders=implode(',',array_fill(0,count(self::EXPECTED_MIGRATIONS),'?'));
        try {
            $statement=$this->pdo->prepare('SELECT migration,batch FROM migrations WHERE migration IN ('.$placeholders.') ORDER BY migration,batch');
            $statement->execute(self::EXPECTED_MIGRATIONS);
            $ledger=phase1cGroupMigrationLedgerRows($statement->fetchAll(PDO::FETCH_ASSOC));
        } catch(Throwable $e) {
            foreach(self::EXPECTED_MIGRATIONS as $migration) $out[]=['migration'=>$migration,'state'=>'UNKNOWN','reason'=>'LEDGER_QUERY_UNAVAILABLE','record_count'=>null,'duplicate_count'=>null,'batches'=>[],'error'=>$this->safeError($e)];
            return $out;
        }

        foreach(self::EXPECTED_MIGRATIONS as $migration) {
            $rows=$ledger[$migration]??[];
            $batches=[];
            $validBatches=true;
            foreach($rows as $batch) {
                if(!is_scalar($batch)||!ctype_digit((string)$batch)) {
                    $validBatches=false;
                    continue;
                }
                $batches[]=(int)$batch;
            }
            $count=count($rows);
            if($count>1) $state='FAIL';
            elseif($count===1) $state=$validBatches?'PASS':'FAIL';
            else $state=$this->absenceProof?'MISSING':'UNKNOWN';
            $out[]=[
                'migration'=>$migration,
                'state'=>$state,
                'record_count'=>$count,
                'duplicate_count'=>max(0,$count-1),
                'batches'=>$batches,
                'batch_values_valid'=>$validBatches,
            ];
        }

        return $out;
    }

    private function normalizeMysqlColumnType(string $type): string
    {
        $type=strtolower(trim($type));

        return preg_replace('/^(tinyint|smallint|mediumint|int|integer|bigint)\\(\\d+\\)/','$1',$type)??$type;
    }

    private function normalizeMysqlDefault(mixed $value): ?string
    {
        if($value===null) return null;
        $value=(string)$value;
        if(strlen($value)>=2&&$value[0]==="'"&&$value[strlen($value)-1]==="'") $value=str_replace("''","'",substr($value,1,-1));

        return $value;
    }

    private function normalizeSql(string $statement): string
    {
        $statement=strtolower(str_replace('`','',$statement));
        $statement=preg_replace('/\\s+/',' ',$statement)??$statement;

        return rtrim(trim($statement),'; ');
    }

    private function referentialActionMatches(string $actual,string $expected): bool
    {
        $normalize=static fn(string $action): string=>strtoupper(trim($action))==='NO ACTION'?'RESTRICT':strtoupper(trim($action));

        return $normalize($actual)===$normalize($expected);
    }

    private function foreignKeys(): array
    {
        try {
            $placeholders=implode(',',array_fill(0,count(self::TABLES),'?'));
            $q=$this->pdo->prepare("SELECT k.CONSTRAINT_NAME,k.TABLE_NAME,k.COLUMN_NAME,k.ORDINAL_POSITION,k.POSITION_IN_UNIQUE_CONSTRAINT,k.REFERENCED_TABLE_SCHEMA,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.MATCH_OPTION,r.UPDATE_RULE,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME AND r.TABLE_NAME=k.TABLE_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.TABLE_NAME IN ($placeholders) AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY k.TABLE_NAME,k.CONSTRAINT_NAME,k.ORDINAL_POSITION");
            $q->execute(self::TABLES);
            return $q->fetchAll();
        } catch(Throwable $e) {return [['state'=>'UNKNOWN','error'=>$this->safeError($e)]];}
    }

    private function queueMapping(): array
    {
        if(!isset($this->tables['data_reverification_queue'])) return ['state'=>$this->absenceProof?'NOT_CHECKED':'UNKNOWN','reason'=>'SOURCE_TABLE_NOT_VISIBLE','records'=>[]];
        if(isset($this->columnErrors['data_reverification_queue'])) return ['state'=>'UNKNOWN','reason'=>'SOURCE_COLUMN_METADATA_UNAVAILABLE','records'=>[]];
        if(!$this->has('data_reverification_queue',['id','subject_type','subject_id','attribute','status'])) return ['state'=>'NOT_CHECKED','reason'=>'SOURCE_COLUMNS_MISSING','records'=>[]];
        $count=(int)$this->pdo->query("SELECT COUNT(*) FROM data_reverification_queue WHERE status='open'")->fetchColumn();
        $rows=$this->pdo->query("SELECT id,subject_type,subject_id,attribute FROM data_reverification_queue WHERE status='open' ORDER BY id LIMIT 6")->fetchAll();
        $target='data_reverification_queue_identities';
        $ready=$this->has($target,['identity_key','subject_type','subject_id','attribute','active_queue_id']);
        $records=[];
        foreach(array_slice($rows,0,5) as $i=>$r) {
            if(!$ready) {$targetState=isset($this->columnErrors[$target])?'UNKNOWN':(!isset($this->tables[$target])&&!$this->absenceProof?'UNKNOWN':'NOT_CHECKED');$records[]=['record_ordinal'=>$i+1,'state'=>$targetState];continue;}
            $key=hash('sha256',json_encode([(string)$r['subject_type'],(int)$r['subject_id'],$r['attribute']],JSON_THROW_ON_ERROR));
            $q=$this->pdo->prepare('SELECT COUNT(*) FROM data_reverification_queue_identities WHERE identity_key=? AND subject_type=? AND subject_id=? AND (attribute <=> ?) AND active_queue_id=?');
            $q->execute([$key,$r['subject_type'],$r['subject_id'],$r['attribute'],$r['id']]); $n=(int)$q->fetchColumn();
            $records[]=['record_ordinal'=>$i+1,'state'=>$n===1?'PASS':'FAIL','mapping_match_count'=>$n];
        }
        return ['state'=>!$ready?'NOT_CHECKED':($count===count($rows)&&$count<=5?'PASS':'FAIL'),'open_record_count'=>$count,'records'=>$records];
    }

    private function alertMapping(): array
    {
        if(!isset($this->tables['monitoring_alerts'])) return ['state'=>$this->absenceProof?'NOT_CHECKED':'UNKNOWN','reason'=>'SOURCE_TABLE_NOT_VISIBLE','records'=>[]];
        if(isset($this->columnErrors['monitoring_alerts'])) return ['state'=>'UNKNOWN','reason'=>'SOURCE_COLUMN_METADATA_UNAVAILABLE','records'=>[]];
        if(!$this->has('monitoring_alerts',['id','dedupe_key','status'])) return ['state'=>'NOT_CHECKED','reason'=>'SOURCE_COLUMNS_MISSING','records'=>[]];
        $count=(int)$this->pdo->query("SELECT COUNT(*) FROM monitoring_alerts WHERE status='open'")->fetchColumn();
        $rows=$this->pdo->query("SELECT id,dedupe_key FROM monitoring_alerts WHERE status='open' ORDER BY id LIMIT 3")->fetchAll();
        $target='monitoring_alert_identities';
        $ready=$this->has($target,['dedupe_key','active_alert_id']);
        $records=[];
        foreach(array_slice($rows,0,2) as $i=>$r) {
            if(!$ready) {$targetState=isset($this->columnErrors[$target])?'UNKNOWN':(!isset($this->tables[$target])&&!$this->absenceProof?'UNKNOWN':'NOT_CHECKED');$records[]=['record_ordinal'=>$i+1,'state'=>$targetState];continue;}
            $q=$this->pdo->prepare('SELECT COUNT(*) FROM monitoring_alert_identities WHERE dedupe_key=? AND active_alert_id=?');
            $q->execute([$r['dedupe_key'],$r['id']]); $n=(int)$q->fetchColumn();
            $records[]=['record_ordinal'=>$i+1,'state'=>$n===1?'PASS':'FAIL','mapping_match_count'=>$n];
        }
        return ['state'=>!$ready?'NOT_CHECKED':($count===count($rows)&&$count<=2?'PASS':'FAIL'),'open_record_count'=>$count,'records'=>$records];
    }

    private function orphanChecks(): array
    {
        $out=[];
        foreach(self::ORPHANS as [$id,$tables,$columns,$sql]) {
            $missing=$this->missing($tables,$columns);
            if($missing) {$unknown=(bool)array_filter($missing,fn($x)=>str_starts_with($x,'metadata:'));$out[]=['id'=>$id,'state'=>$unknown?'UNKNOWN':'NOT_CHECKED','missing_prerequisites'=>$missing];continue;}
            try {$bad=(bool)$this->pdo->query($sql)->fetchColumn();$out[]=['id'=>$id,'state'=>$bad?'FAIL':'PASS','orphan_exists'=>$bad];}
            catch(Throwable $e) {$out[]=['id'=>$id,'state'=>'UNKNOWN','error'=>$this->safeError($e)];}
        }
        return $out;
    }

    private function duplicateChecks(): array
    {
        $out=[];
        foreach(self::DUPLICATES as [$id,$table,$columns,$where]) {
            $missing=$this->missing([$table],[$table=>$columns]);
            if($missing) {$unknown=(bool)array_filter($missing,fn($x)=>str_starts_with($x,'metadata:'));$out[]=['id'=>$id,'state'=>$unknown?'UNKNOWN':'NOT_CHECKED','missing_prerequisites'=>$missing];continue;}
            try {
                $group=implode(',',array_map([$this,'identifier'],$columns));
                $sql='SELECT EXISTS(SELECT 1 FROM '.$this->identifier($table).' WHERE '.$where.' GROUP BY '.$group.' HAVING COUNT(*)>1 LIMIT 1)';
                $bad=(bool)$this->pdo->query($sql)->fetchColumn();
                $out[]=['id'=>$id,'state'=>$bad?'FAIL':'PASS','duplicate_group_exists'=>$bad];
            } catch(Throwable $e) {$out[]=['id'=>$id,'state'=>'UNKNOWN','error'=>$this->safeError($e)];}
        }
        return $out;
    }

    private function hasCompleteSchemaMetadataVisibility(): bool
    {
        try {
            // Restrict the proof to direct global or current-schema SELECT grants.
            // Role-derived or table-by-table privileges are deliberately insufficient:
            // in those cases hidden objects remain UNKNOWN rather than being called absent.
            $principal = "CONCAT(QUOTE(SUBSTRING(CURRENT_USER(), 1, LENGTH(CURRENT_USER()) - LENGTH(SUBSTRING_INDEX(CURRENT_USER(), '@', -1)) - 1)), '@', QUOTE(SUBSTRING_INDEX(CURRENT_USER(), '@', -1)))";
            $global = $this->pdo->query(
                "SELECT PRIVILEGE_TYPE FROM information_schema.USER_PRIVILEGES WHERE GRANTEE = {$principal}"
            )->fetchAll(PDO::FETCH_COLUMN);
            $schemaQuery = $this->pdo->query(
                "SELECT PRIVILEGE_TYPE FROM information_schema.SCHEMA_PRIVILEGES WHERE TABLE_SCHEMA = DATABASE() AND GRANTEE = {$principal}"
            );
            $schema = $schemaQuery->fetchAll(PDO::FETCH_COLUMN);
            return phase1cMetadataVisibilityComplete($global, $schema);
        } catch(Throwable $e) {
            return false;
        }
    }

    private function hasCompleteTriggerMetadataVisibility(): bool
    {
        try {
            $principal = "CONCAT(QUOTE(SUBSTRING(CURRENT_USER(), 1, LENGTH(CURRENT_USER()) - LENGTH(SUBSTRING_INDEX(CURRENT_USER(), '@', -1)) - 1)), '@', QUOTE(SUBSTRING_INDEX(CURRENT_USER(), '@', -1)))";
            $global = $this->pdo->query(
                "SELECT PRIVILEGE_TYPE FROM information_schema.USER_PRIVILEGES WHERE GRANTEE = {$principal}"
            )->fetchAll(PDO::FETCH_COLUMN);
            $schema = $this->pdo->query(
                "SELECT PRIVILEGE_TYPE FROM information_schema.SCHEMA_PRIVILEGES WHERE TABLE_SCHEMA = DATABASE() AND GRANTEE = {$principal}"
            )->fetchAll(PDO::FETCH_COLUMN);

            return phase1cTriggerMetadataVisibilityComplete($global,$schema);
        } catch(Throwable $e) {
            return false;
        }
    }

    private function has(string $table,array $columns): bool
    {
        if(!isset($this->tables[$table])) return false;
        foreach($columns as $c) if(!isset($this->columns[$table][$c])) return false;
        return true;
    }

    private function missing(array $tables,array $columns): array
    {
        $out=[];
        foreach($tables as $t) if(!isset($this->tables[$t])) $out[]=$this->absenceProof?'table:'.$t:'metadata:table:'.$t;
        foreach($columns as $t=>$names) {
            if(isset($this->columnErrors[$t])) {$out[]='metadata:columns:'.$t;continue;}
            foreach($names as $c) if(isset($this->tables[$t])&&!isset($this->columns[$t][$c])) $out[]='column:'.$t.'.'.$c;
        }
        sort($out); return $out;
    }

    private function identifier(string $name): string
    {
        if(!preg_match('/^[A-Za-z0-9_]+$/',$name)) throw new RuntimeException('Diagnostic identifier rejected.');
        return chr(96).$name.chr(96);
    }

    private function safeDefault(string $column, mixed $value): mixed
    {
        return phase1cSafeMetadataDefault($column, $value);
    }

    private function sanitizeCreateSql(string $sql): string
    {
        // Comments are non-structural and may contain operator-supplied text.
        $sql=preg_replace("/\\s+COMMENT\\s+'(?:''|[^'])*'/i",'',$sql)??$sql;
        // Do not export literal defaults for credential-like columns.
        $sql=preg_replace_callback('/(^\\s*\\x60?([A-Za-z0-9_]+)\\x60?\\s+[^,\\n]*?\\bDEFAULT\\s+)(?:\\x27(?:\\x27\\x27|[^\\x27])*\\x27|[^\\s,]+)/mi',function($m){
            return preg_match('/password|secret|token|credential|private.?key|api.?key/i',$m[2])?$m[1]."'[REDACTED]'":$m[0];
        },$sql)??$sql;
        return preg_replace('/\\bAUTO_INCREMENT=\\d+/i','AUTO_INCREMENT=[OMITTED]',$sql)??$sql;
    }

    private function safeError(Throwable $e): array
    {
        $i=$e instanceof PDOException && is_array($e->errorInfo??null)?$e->errorInfo:[];
        return ['exception_class'=>(new ReflectionClass($e))->getShortName(),'sqlstate'=>(string)($i[0]??($e->getCode()?:'unavailable')),'vendor_code'=>(string)($i[1]??'unavailable')];
    }

    private function sortKeys(array $v): array
    {
        foreach($v as &$x) if(is_array($x)&&!array_is_list($x)) $x=$this->sortKeys($x);
        unset($x); if(!array_is_list($v)) ksort($v); return $v;
    }
}

function phase1cDiagnosticPdo(string $root): PDO
{
    require_once $root.'/vendor/autoload.php';
    $app=require $root.'/bootstrap/app.php';
    $kernel=$app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Reuse Laravel's configured connection; do not construct a second DSN,
    // choose another host, or read a different credential source.
    $connection=Illuminate\Support\Facades\DB::connection();
    if($connection->getDriverName()!=='mysql') throw new RuntimeException('Unsupported configured database driver.');
    $pdo=$connection->getPdo();
    if(!$pdo instanceof PDO || $pdo->inTransaction()) throw new RuntimeException('Configured Laravel PDO connection is unavailable or already in a transaction.');

    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
    return $pdo;
}

if(PHP_SAPI==='cli' && realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__) {
    try {
        $root=$argv[1]??'';
        if($root===''||!str_starts_with($root,'/')) throw new RuntimeException('Invalid project root.');
        $expectedDatabase=getenv('EXPECTED_DATABASE_NAME');
        if (!is_string($expectedDatabase) || $expectedDatabase === '') throw new RuntimeException('Protected expected database identity is unavailable.');
        $e=(new Phase1CReadOnlyDiagnostic(phase1cDiagnosticPdo($root)))->collect($expectedDatabase);
        echo json_encode($e,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL;
    } catch(Throwable $e) {
        $i=$e instanceof PDOException&&is_array($e->errorInfo??null)?$e->errorInfo:[];
        echo json_encode(['diagnostic_state'=>'FAILED_SAFE','exception_class'=>(new ReflectionClass($e))->getShortName(),'sqlstate'=>(string)($i[0]??($e->getCode()?:'unavailable')),'vendor_code'=>(string)($i[1]??'unavailable')],JSON_THROW_ON_ERROR).PHP_EOL;
        exit(1);
    }
}

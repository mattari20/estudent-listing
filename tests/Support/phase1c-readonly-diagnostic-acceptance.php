<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/scripts/phase1c/readonly-schema-diagnostic.php';

function assertDiagnostic(bool $ok,string $message): void { if(!$ok) throw new RuntimeException('acceptance_failed: '.$message); }
function collectDiagnostic(PDO $pdo, ?string $expectedDatabase = null): array { return (new Phase1CReadOnlyDiagnostic($pdo))->collect($expectedDatabase ?? (string)getenv('DB_DATABASE')); }

assertDiagnostic(phase1cClassifyMigrationObjectLedgerState('PASS', 'PASS')['state'] === 'PASS', 'ledger and all expected objects present passes');
assertDiagnostic(phase1cClassifyMigrationObjectLedgerState('PASS', 'MISSING')['state'] === 'FAIL', 'ledger row with missing objects fails');
assertDiagnostic(phase1cClassifyMigrationObjectLedgerState('MISSING', 'PASS')['reason'] === 'EXPECTED_OBJECTS_PRESENT_WITHOUT_LEDGER_ENTRY', 'objects without ledger row fail');
assertDiagnostic(phase1cClassifyMigrationObjectLedgerState('MISSING', 'FAIL')['reason'] === 'PARTIAL_OBJECT_APPLICATION_WITHOUT_LEDGER_ENTRY', 'partial objects without ledger row fail');
assertDiagnostic(phase1cClassifyMigrationObjectLedgerState('UNKNOWN', 'PASS')['state'] === 'UNKNOWN', 'insufficient ledger evidence remains unknown');

// Pure full-ledger contract tests run identically on MySQL and MariaDB.
$ledgerSources = ['2026_01_01_000001_first', '2026_01_02_000001_second', '2026_01_03_000001_third'];
$ledgerComplete = phase1cAnalyzeFullMigrationLedger($ledgerSources, [
    ['id' => 1, 'migration' => $ledgerSources[0], 'batch' => 1],
    ['id' => 2, 'migration' => $ledgerSources[1], 'batch' => 1],
    ['id' => 3, 'migration' => $ledgerSources[2], 'batch' => 2],
]);
assertDiagnostic($ledgerComplete['state'] === 'PASS', 'complete source and ledger inventory passes');
assertDiagnostic($ledgerComplete['source_file_count'] === 3 && $ledgerComplete['ledger_row_count'] === 3, 'full ledger counts are exact');
assertDiagnostic($ledgerComplete['ordering_state'] === 'PASS', 'migration ordering is checked when sequence IDs exist');

$ledgerMissing = phase1cAnalyzeFullMigrationLedger($ledgerSources, [
    ['id' => 1, 'migration' => $ledgerSources[0], 'batch' => 1],
]);
assertDiagnostic($ledgerMissing['state'] === 'MISSING' && $ledgerMissing['source_files_not_recorded'] === [$ledgerSources[1], $ledgerSources[2]], 'unrecorded source migrations remain explicitly missing');

$ledgerUnexpected = phase1cAnalyzeFullMigrationLedger($ledgerSources, [
    ['id' => 1, 'migration' => $ledgerSources[0], 'batch' => 1],
    ['id' => 2, 'migration' => '2025_12_31_000001_untracked', 'batch' => 1],
]);
assertDiagnostic($ledgerUnexpected['state'] === 'FAIL' && $ledgerUnexpected['ledger_entries_without_source_files'] === ['2025_12_31_000001_untracked'], 'unexpected ledger entries fail');

$ledgerDuplicate = phase1cAnalyzeFullMigrationLedger($ledgerSources, [
    ['id' => 1, 'migration' => $ledgerSources[0], 'batch' => 1],
    ['id' => 2, 'migration' => $ledgerSources[0], 'batch' => 2],
]);
assertDiagnostic($ledgerDuplicate['state'] === 'FAIL' && $ledgerDuplicate['duplicate_ledger_entries'][0]['occurrences'] === 2, 'duplicate ledger rows fail');

$ledgerInvalidBatch = phase1cAnalyzeFullMigrationLedger($ledgerSources, [
    ['id' => 1, 'migration' => $ledgerSources[0], 'batch' => 0],
]);
assertDiagnostic($ledgerInvalidBatch['state'] === 'FAIL' && $ledgerInvalidBatch['invalid_ledger_rows'][0]['reason'] === 'INVALID_BATCH_VALUE', 'invalid batch values fail');

$ledgerOrder = phase1cAnalyzeFullMigrationLedger($ledgerSources, [
    ['id' => 1, 'migration' => $ledgerSources[1], 'batch' => 1],
    ['id' => 2, 'migration' => $ledgerSources[0], 'batch' => 1],
]);
assertDiagnostic($ledgerOrder['state'] === 'FAIL' && $ledgerOrder['ordering_state'] === 'FAIL', 'out-of-order migrations within a batch fail');

$ledgerNoSequence = phase1cAnalyzeFullMigrationLedger($ledgerSources, [
    ['migration' => $ledgerSources[0], 'batch' => 1],
]);
assertDiagnostic($ledgerNoSequence['ordering_state'] === 'UNKNOWN', 'ordering is UNKNOWN without a sequence column');

$ledgerBatchGap = phase1cAnalyzeFullMigrationLedger($ledgerSources, [
    ['id' => 1, 'migration' => $ledgerSources[0], 'batch' => 1],
    ['id' => 2, 'migration' => $ledgerSources[1], 'batch' => 3],
    ['id' => 3, 'migration' => $ledgerSources[2], 'batch' => 3],
]);
assertDiagnostic($ledgerBatchGap['state'] === 'PASS' && $ledgerBatchGap['batch_gaps'] === [2] && $ledgerBatchGap['batch_gap_state'] === 'NOT_CHECKED', 'batch gaps are exposed without assuming rollback history is corrupt');

$pdo=new PDO('mysql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT')?:3306).';dbname='.getenv('DB_DATABASE').';charset=utf8mb4',getenv('DB_USERNAME'),getenv('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
$tables=['assertions','change_events','data_reverification_queue','data_reverification_queue_identities','enrichment_import_rows','evidence','governed_execution_leases','governed_execution_run_events','governed_execution_runs','identity_match_candidates','identity_resolution_decisions','import_batches','import_conflict_events','import_conflicts','import_promotion_decisions','import_promotion_events','import_reconciliation_events','import_rows','institute_discovery_run_outcomes','institutions','migrations','monitoring_alert_identities','monitoring_alerts','monitoring_policy_run_sources','monitoring_policy_runs','official_source_refresh_events','password_reset_tokens','provenance_review_events','reverification_events','security_audit_events','source_change_observations','source_monitoring_policies','source_retrieval_runs','source_snapshot_reconciliation_requests','source_snapshots','sources','structured_enrichment_promotion_events','users','verification_decisions'];
foreach($tables as $t) {
    if ($t === 'password_reset_tokens') {
        $pdo->exec('CREATE TABLE password_reset_tokens (email VARCHAR(255) NOT NULL PRIMARY KEY) ENGINE=InnoDB');
        continue;
    }
    $id=$t==='governed_execution_runs'?'id CHAR(36) NOT NULL PRIMARY KEY':'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
    $pdo->exec('CREATE TABLE '.$t.' ('.$id.') ENGINE=InnoDB');
}

$expectedMigrations=[
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
$pdo->exec('ALTER TABLE migrations ADD COLUMN migration VARCHAR(255) NOT NULL, ADD COLUMN batch INT UNSIGNED NOT NULL');
$ledgerInsert=$pdo->prepare('INSERT INTO migrations(migration,batch) VALUES(?,?)');
foreach($expectedMigrations as $index=>$migration) {
    if($index===1) continue;
    $ledgerInsert->execute([$migration,1]);
}
$ledgerInsert->execute([$expectedMigrations[0],2]);

$columns=[
'identity_resolution_decisions'=>['import_row_id BIGINT UNSIGNED NULL','decision_identity_key CHAR(64) NULL','institution_id BIGINT UNSIGNED NULL','decision VARCHAR(30) NULL','actor_type VARCHAR(40) NULL','actor_id BIGINT UNSIGNED NULL','reason TEXT NULL','decided_at TIMESTAMP NULL','created_at TIMESTAMP NULL'],
'users'=>['must_change_password TINYINT(1) NOT NULL DEFAULT 0'],
'import_batches'=>['duplicate_rows INT UNSIGNED NOT NULL DEFAULT 0','discovery_identity_key VARCHAR(64) NULL'],
'password_reset_tokens'=>['token VARCHAR(255) NOT NULL','created_at TIMESTAMP NULL DEFAULT NULL'],
'security_audit_events'=>['monitoring_idempotency_key VARCHAR(191) NULL'],
'verification_decisions'=>['assertion_id BIGINT UNSIGNED NULL'],
'import_reconciliation_events'=>['import_batch_id BIGINT UNSIGNED NULL'],
'import_promotion_decisions'=>['import_row_id BIGINT UNSIGNED NULL'],
'import_promotion_events'=>['import_row_id BIGINT UNSIGNED NULL','import_batch_id BIGINT UNSIGNED NULL'],
'reverification_events'=>['queue_id BIGINT UNSIGNED NULL'],
'structured_enrichment_promotion_events'=>['enrichment_import_row_id BIGINT UNSIGNED NULL','import_batch_id BIGINT UNSIGNED NULL'],
'import_conflict_events'=>['import_conflict_id BIGINT UNSIGNED NULL','idempotency_key CHAR(64) NULL','occurred_at TIMESTAMP NULL'],
'governed_execution_run_events'=>['run_id CHAR(36) NOT NULL','event_type VARCHAR(30) NOT NULL','occurred_at TIMESTAMP NOT NULL'],
'monitoring_policy_runs'=>['heartbeat_at TIMESTAMP NULL DEFAULT NULL','source_monitoring_policy_id BIGINT UNSIGNED NOT NULL','execution_run_id CHAR(36) NULL DEFAULT NULL','started_at TIMESTAMP NOT NULL'],
'monitoring_policy_run_sources'=>['monitoring_policy_run_id BIGINT UNSIGNED NOT NULL','source_id BIGINT UNSIGNED NOT NULL','retrieval_run_id BIGINT UNSIGNED NULL DEFAULT NULL','observation_id BIGINT UNSIGNED NULL DEFAULT NULL','status VARCHAR(20) NULL'],
'monitoring_alert_identities'=>['dedupe_key VARCHAR(40) NULL','active_alert_id BIGINT UNSIGNED NULL'],
'monitoring_alerts'=>['dedupe_key VARCHAR(40) NULL','status VARCHAR(20) NULL'],
'data_reverification_queue'=>['subject_type VARCHAR(255) NULL','subject_id BIGINT UNSIGNED NULL','attribute VARCHAR(255) NULL','status VARCHAR(20) NULL'],
'data_reverification_queue_identities'=>['identity_key VARCHAR(64) NULL','subject_type VARCHAR(255) NULL','subject_id BIGINT UNSIGNED NULL','attribute VARCHAR(255) NULL','active_queue_id BIGINT UNSIGNED NULL'],
'source_change_observations'=>['current_snapshot_id BIGINT UNSIGNED NULL'],
'institute_discovery_run_outcomes'=>['idempotency_key VARCHAR(64) NULL'],
'change_events'=>['source_change_assertion_link_id BIGINT UNSIGNED NULL'],
'source_retrieval_runs'=>["idempotency_key VARCHAR(64) NULL DEFAULT 'NULL'"],
'source_snapshots'=>['capture_key VARCHAR(64) NULL DEFAULT NULL'],
'evidence'=>['discovery_identity_key VARCHAR(64) NULL'],
'import_rows'=>['discovery_identity_key VARCHAR(64) NULL'],
'assertions'=>['discovery_identity_key VARCHAR(64) NULL'],
'identity_match_candidates'=>['identity_key CHAR(64) NULL'],
'import_conflicts'=>['identity_key CHAR(64) NULL'],
'source_snapshot_reconciliation_requests'=>['identity_key VARCHAR(64) NULL','source_id BIGINT UNSIGNED NULL','status VARCHAR(20) NULL','current_snapshot_id BIGINT UNSIGNED NULL'],
'governed_execution_leases'=>['task_key VARCHAR(120) NOT NULL','owner_token CHAR(36) NOT NULL','run_id CHAR(36) NOT NULL','expires_at TIMESTAMP NOT NULL'],
];
foreach($columns as $t=>$defs) foreach($defs as $def) $pdo->exec('ALTER TABLE '.$t.' ADD COLUMN '.$def);

$indexes=[
['governed_execution_leases','governed_execution_leases_task_key_unique','task_key',true],
['governed_execution_leases','governed_execution_leases_expires_at_index','expires_at',false],
['source_retrieval_runs','source_retrieval_runs_idempotency_key_unique','idempotency_key',true],
['source_snapshots','source_snapshots_capture_key_unique','capture_key',true],
['source_change_observations','source_observations_current_snapshot_unique','current_snapshot_id',true],
['institute_discovery_run_outcomes','institute_discovery_run_outcomes_idempotency_key_unique','idempotency_key',true],
['change_events','change_events_source_link_unique','source_change_assertion_link_id',true],
['security_audit_events','security_audit_events_monitoring_idempotency_key_unique','monitoring_idempotency_key',true],
['monitoring_policy_run_sources','monitoring_policy_run_source_unique','monitoring_policy_run_id,source_id',true],
['import_batches','import_batches_discovery_identity_unique','discovery_identity_key',true],
['evidence','evidence_discovery_identity_unique','discovery_identity_key',true],
['import_rows','import_rows_discovery_identity_unique','discovery_identity_key',true],
['assertions','assertions_discovery_identity_unique','discovery_identity_key',true],
['identity_match_candidates','identity_match_candidates_identity_key_unique','identity_key',true],
['identity_resolution_decisions','identity_resolution_decisions_identity_key_unique','decision_identity_key',true],
['import_conflicts','import_conflicts_identity_key_unique','identity_key',true],
['import_conflict_events','import_conflict_events_idempotency_key_unique','idempotency_key',true],
['source_snapshot_reconciliation_requests','source_snapshot_reconciliation_requests_identity_key_unique','identity_key',true],
['source_snapshot_reconciliation_requests','snapshot_reconciliation_source_status_idx','source_id,status',false],
['source_snapshot_reconciliation_requests','snapshot_reconciliation_current_status_idx','current_snapshot_id,status',false],
['import_conflict_events','import_conflict_events_import_conflict_id_occurred_at_index','import_conflict_id,occurred_at',false],
['monitoring_policy_run_sources','monitoring_policy_source_status_idx','source_id,status',false],
];
foreach($indexes as [$t,$name,$cols,$unique]) $pdo->exec('CREATE '.($unique?'UNIQUE ':'').'INDEX '.$name.' ON '.$t.' ('.$cols.')');
$pdo->exec('ALTER TABLE verification_decisions ADD CONSTRAINT fixture_verification_assertion_fk FOREIGN KEY (assertion_id) REFERENCES assertions(id)');
$pdo->exec('ALTER TABLE verification_decisions DROP FOREIGN KEY fixture_verification_assertion_fk');
$pdo->exec('ALTER TABLE verification_decisions ADD CONSTRAINT verification_decision_assertion_restrict_fk FOREIGN KEY (assertion_id) REFERENCES assertions(id) ON DELETE RESTRICT ON UPDATE RESTRICT');
$pdo->exec('ALTER TABLE identity_resolution_decisions ADD CONSTRAINT identity_decision_row_restrict_fk FOREIGN KEY (import_row_id) REFERENCES import_rows(id) ON DELETE CASCADE');
$triggerName='audit_immut_'.substr(hash('sha256','security_audit_events'),0,16).'_d';
$pdo->exec("CREATE TRIGGER {$triggerName} BEFORE DELETE ON security_audit_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'incorrect trigger body'");
$pdo->exec("CREATE TRIGGER identity_decision_delete_guard BEFORE DELETE ON identity_resolution_decisions FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identity decision history cannot be deleted'");
$identityUpdateConditions=[
    'OLD.decision_identity_key IS NULL','NEW.decision_identity_key IS NOT NULL','OLD.id <=> NEW.id',
    'OLD.import_row_id <=> NEW.import_row_id','OLD.institution_id <=> NEW.institution_id',
    'OLD.decision <=> NEW.decision','OLD.actor_type <=> NEW.actor_type','OLD.actor_id <=> NEW.actor_id',
    'OLD.reason <=> NEW.reason','OLD.decided_at <=> NEW.decided_at','OLD.created_at <=> NEW.created_at',
];
$identityUpdateBody="BEGIN IF NOT (".implode(' AND ',$identityUpdateConditions).") THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identity decision history is immutable except one-time key enrichment'; END IF; END";
$pdo->exec('CREATE TRIGGER identity_decision_update_guard BEFORE UPDATE ON identity_resolution_decisions FOR EACH ROW '.$identityUpdateBody);

$q=$pdo->prepare('INSERT INTO data_reverification_queue(subject_type,subject_id,attribute,status) VALUES(?,?,?,?)');
$qi=$pdo->prepare('INSERT INTO data_reverification_queue_identities(identity_key,subject_type,subject_id,attribute,active_queue_id) VALUES(?,?,?,?,?)');
for($i=1;$i<=5;$i++) {$q->execute(['institution',$i,'name','open']);$id=(int)$pdo->lastInsertId();$key=hash('sha256',json_encode(['institution',$i,'name'],JSON_THROW_ON_ERROR));$qi->execute([$key,'institution',$i,'name',$id]);}
$a=$pdo->prepare('INSERT INTO monitoring_alerts(dedupe_key,status) VALUES(?,?)');
$ai=$pdo->prepare('INSERT INTO monitoring_alert_identities(dedupe_key,active_alert_id) VALUES(?,?)');
for($i=1;$i<=2;$i++) {$key='fixture-alert-'.$i;$a->execute([$key,'open']);$id=(int)$pdo->lastInsertId();$ai->execute([$key,$id]);}
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$pdo->exec('INSERT INTO verification_decisions(assertion_id) VALUES(999999)');
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

$rowCounts=function() use($pdo,$tables): array {$r=[];foreach($tables as $t)$r[$t]=(int)$pdo->query('SELECT COUNT(*) FROM '.$t)->fetchColumn();return $r;};
$schemaCounts=function() use($pdo): array { return [
    'tables'=>(int)$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'")->fetchColumn(),
    'columns'=>(int)$pdo->query('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()')->fetchColumn(),
    'indexes'=>(int)$pdo->query('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE()')->fetchColumn(),
    'foreign_keys'=>(int)$pdo->query('SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL')->fetchColumn(),
    'triggers'=>(int)$pdo->query('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE()')->fetchColumn(),
]; };
$before=$rowCounts();$beforeSchema=$schemaCounts();
$identityRejected=false;
try { collectDiagnostic($pdo, 'phase1c_wrong_database'); } catch (RuntimeException $e) { $identityRejected=true; }
assertDiagnostic($identityRejected && !$pdo->inTransaction(),'unexpected selected database rejected before schema evidence');
assertDiagnostic($before===$rowCounts() && $beforeSchema===$schemaCounts(),'database identity rejection performs no schema/data changes');
$one=collectDiagnostic($pdo);$two=collectDiagnostic($pdo);
assertDiagnostic($before===$rowCounts(),'initial diagnostic collector caused no row changes');
assertDiagnostic($beforeSchema===$schemaCounts(),'initial diagnostic collector caused no schema-object changes');
assertDiagnostic(json_encode($one,JSON_UNESCAPED_SLASHES)===json_encode($two,JSON_UNESCAPED_SLASHES),'deterministic evidence');
assertDiagnostic($one['schema_version']==='phase1c-readonly-diagnostic/v3','versioned schema comparator evidence');
$sourceContract=$one['source_schema_reconciliation'];
assertDiagnostic($sourceContract['contract']==='phase1d2b-schema-comparison/v1' && $sourceContract['table_count']===63,'all 63 source schema scope entries represented');
assertDiagnostic($sourceContract['state']==='FAIL' && $sourceContract['complete_table_count']===0 && $sourceContract['schema_equivalence_proven']===false && $sourceContract['observed_metadata_collected']===true,'partial disposable schema must fail source reconciliation without claiming equivalence');
assertDiagnostic(count($sourceContract['results'])===63,'all 63 source table comparison statuses are present');
assertDiagnostic(count($sourceContract['unexpected_tables'])>=0,'unexpected table inventory is explicitly represented');
$sourceMissingObjectCount=0;
foreach($sourceContract['results'] as $tableName => $tableResult) {
    assertDiagnostic(in_array($tableResult['state'], ['FAIL','NOT_CHECKED','UNKNOWN'], true),'partial disposable schema cannot produce a table-level PASS');
    assertDiagnostic(($tableResult['objects']['table']['state'] ?? null)==='NOT_CHECKED','table-level comparison remains NOT_CHECKED while engine options are server-default');
    foreach(['columns','indexes','foreign_keys','triggers'] as $kind) {
        foreach(($tableResult['objects'][$kind] ?? []) as $objectResult) {
            if($objectResult['state']==='MISSING') $sourceMissingObjectCount++;
        }
    }
}
assertDiagnostic($sourceMissingObjectCount>0,'known absent objects in a fully visible disposable schema are reported MISSING');
$fullSource=$one['full_source_migration_reconciliation'];
assertDiagnostic($fullSource['source_count']===97 && count($fullSource['migration_object_reconciliation'])===97,'all 97 source migrations receive ledger/object reconciliation rows');
$fullObjectStates=array_count_values(array_column($fullSource['migration_object_reconciliation'],'object_state'));
assertDiagnostic(array_sum($fullObjectStates)===97,'all 97 migrations receive one object state');
assertDiagnostic(($fullSource['source_review_state_counts']['REVIEWED_NOOP'] ?? 0)===23,'all 23 no-op source dispositions remain preserved');
assertDiagnostic(($fullSource['source_review_state_counts']['SOURCE_REVIEWED_COMPLETE'] ?? 0)===74,'all 74 individually completed source reviews remain separately classified');
assertDiagnostic(($fullSource['source_review_state_counts']['SOURCE_REVIEWED_PARTIAL'] ?? 0)===0,'all 0 unresolved source reviews remain partial');
foreach($fullSource['migration_object_reconciliation'] as $row) {
    if($row['source_review_state']==='REVIEWED_NOOP') assertDiagnostic($row['object_state']==='PASS','reviewed no-op must remain object-neutral: '.$row['migration']);
    if($row['source_review_state']==='SOURCE_REVIEWED_COMPLETE') assertDiagnostic(in_array($row['object_state'],['PASS','FAIL','UNKNOWN'],true),'fully mapped source review must not be silently NOT_CHECKED: '.$row['migration']);
    if($row['source_review_state']==='SOURCE_REVIEWED_PARTIAL') assertDiagnostic($row['object_state']!=='PASS','partial source review cannot claim object PASS: '.$row['migration']);
}
assertDiagnostic($one['registry_counts']['tables']===63 && $one['registry_counts']['expected_indexes']===23 && $one['registry_counts']['expected_columns']===50 && $one['registry_counts']['expected_foreign_keys']===23 && $one['registry_counts']['expected_triggers']===24 && $one['registry_counts']['expected_migrations']===18 && $one['registry_counts']['orphan_checks']===18 && $one['registry_counts']['duplicate_checks']===14,'registry sizes');
assertDiagnostic(count($one['tables'])===63 && count($one['expected_index_reconciliation'])===23,'complete table/index metadata');
assertDiagnostic(count($one['expected_column_reconciliation'])===50 && count($one['expected_foreign_key_reconciliation'])===23 && count($one['expected_trigger_reconciliation'])===24 && count($one['migration_ledger_reconciliation'])===18,'complete reconciliation sections');
assertDiagnostic(count($one['orphan_checks'])===18 && count($one['duplicate_checks'])===14,'individual check reporting');

$fullLedger = $one['full_migration_ledger_inventory'];
assertDiagnostic($fullLedger['source_file_count'] >= 18 && $fullLedger['ledger_row_count'] === 18, 'full migration source and ledger inventory counts');
assertDiagnostic(in_array($expectedMigrations[1], $fullLedger['source_files_not_recorded'], true), 'full ledger exposes source files absent from the ledger');
assertDiagnostic($fullLedger['ledger_entries_without_source_files'] === [], 'all fixture ledger entries resolve to migration source files');
assertDiagnostic($fullLedger['state'] === 'FAIL' && $fullLedger['duplicate_ledger_entries'][0]['migration'] === $expectedMigrations[0], 'full ledger reports duplicate source migration rows');

$objectConsistency = $one['migration_object_reconciliation'];
assertDiagnostic(count($objectConsistency) === 18, 'all 18 target migrations have object/ledger consistency results');
$missingLedgerButObjects = array_values(array_filter($objectConsistency, static fn (array $row): bool => $row['migration'] === $expectedMigrations[1]))[0];
assertDiagnostic($missingLedgerButObjects['ledger_state'] === 'MISSING' && $missingLedgerButObjects['object_state'] === 'PASS' && $missingLedgerButObjects['consistency_state'] === 'FAIL', 'objects present without their migration ledger entry fail');

$primaryIndex = static function (array $evidence): array {
    $matches = array_values(array_filter(
        $evidence['expected_index_reconciliation'],
        static fn (array $row): bool => $row['table'] === 'password_reset_tokens' && $row['name'] === 'PRIMARY'
    ));
    return $matches[0] ?? [];
};
$expectedPrimary = $primaryIndex($one);
assertDiagnostic(($expectedPrimary['columns'] ?? null) === ['email'], 'password reset registry expects email primary key');
assertDiagnostic(($expectedPrimary['state'] ?? null) === 'PASS', 'email primary key is accepted');
$idColumnCount = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='password_reset_tokens' AND COLUMN_NAME='id'")->fetchColumn();
assertDiagnostic($idColumnCount === 0, 'password reset fixture does not introduce an id column');

// A different single-column primary key must fail the email-keyed contract.
$pdo->exec('ALTER TABLE password_reset_tokens DROP PRIMARY KEY, ADD PRIMARY KEY (token)');
$wrongPrimary = $primaryIndex(collectDiagnostic($pdo));
assertDiagnostic(($wrongPrimary['state'] ?? null) === 'FAIL' && ($wrongPrimary['observed']['columns'] ?? null) === ['token'], 'wrong primary-key mapping is rejected');

// Composite primary keys are supported as ordered column lists and rejected when they differ.
$pdo->exec('ALTER TABLE password_reset_tokens MODIFY created_at TIMESTAMP NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (email, created_at)');
$compositePrimary = $primaryIndex(collectDiagnostic($pdo));
assertDiagnostic(($compositePrimary['state'] ?? null) === 'FAIL' && ($compositePrimary['observed']['columns'] ?? null) === ['email', 'created_at'], 'composite primary key is explicitly compared and rejected');
$pdo->exec('ALTER TABLE password_reset_tokens DROP PRIMARY KEY, MODIFY created_at TIMESTAMP NULL DEFAULT NULL, ADD PRIMARY KEY (email)');

assertDiagnostic($one['queue_mapping']['state']==='PASS' && count($one['queue_mapping']['records'])===5,'five sanitized queue mappings');
assertDiagnostic($one['alert_mapping']['state']==='PASS' && count($one['alert_mapping']['records'])===2,'two sanitized alert mappings');

assertDiagnostic($one['orphan_checks'][1]['state']==='FAIL','orphan failure');
$column=array_values(array_filter($one['expected_column_reconciliation'],fn($r)=>$r['table']==='source_change_observations'&&$r['column']==='current_snapshot_id'))[0];
assertDiagnostic($column['state']==='FAIL','column nullability mismatch');
$validColumn=array_values(array_filter($one['expected_column_reconciliation'],fn($r)=>$r['table']==='users'&&$r['column']==='must_change_password'))[0];
assertDiagnostic($validColumn['state']==='PASS','column type, nullability and default match');
$nullableDefaultColumn=array_values(array_filter($one['expected_column_reconciliation'],fn($r)=>$r['table']==='password_reset_tokens'&&$r['column']==='created_at'))[0];
assertDiagnostic($nullableDefaultColumn['state']==='PASS','nullable column default matches');
$sqlNullDefaultColumn=array_values(array_filter($one['expected_column_reconciliation'],fn($r)=>$r['table']==='source_snapshots'&&$r['column']==='capture_key'))[0];
assertDiagnostic($sqlNullDefaultColumn['state']==='PASS','SQL NULL default matches');
$literalNullDefaultColumn=array_values(array_filter($one['expected_column_reconciliation'],fn($r)=>$r['table']==='source_retrieval_runs'&&$r['column']==='idempotency_key'))[0];
assertDiagnostic($literalNullDefaultColumn['state']==='FAIL','literal string NULL does not match SQL NULL default');
$foreignKey=array_values(array_filter($one['expected_foreign_key_reconciliation'],fn($r)=>$r['name']==='identity_decision_row_restrict_fk'))[0];
assertDiagnostic($foreignKey['state']==='FAIL','foreign-key delete-action mismatch');
$validForeignKey=array_values(array_filter($one['expected_foreign_key_reconciliation'],fn($r)=>$r['name']==='verification_decision_assertion_restrict_fk'))[0];
assertDiagnostic($validForeignKey['state']==='PASS','exact foreign-key definition matches');
$badTriggerName='audit_immut_'.substr(hash('sha256','security_audit_events'),0,16).'_d';
$trigger=array_values(array_filter($one['expected_trigger_reconciliation'],fn($r)=>$r['name']===$badTriggerName))[0];
assertDiagnostic($trigger['state']==='FAIL','trigger body mismatch');
$validDeleteTrigger=array_values(array_filter($one['expected_trigger_reconciliation'],fn($r)=>$r['name']==='identity_decision_delete_guard'))[0];
$validUpdateTrigger=array_values(array_filter($one['expected_trigger_reconciliation'],fn($r)=>$r['name']==='identity_decision_update_guard'))[0];
assertDiagnostic($validDeleteTrigger['state']==='PASS'&&$validUpdateTrigger['state']==='PASS','exact simple and compound trigger definitions match');
$duplicateMigration=array_values(array_filter($one['migration_ledger_reconciliation'],fn($r)=>$r['migration']===$expectedMigrations[0]))[0];
assertDiagnostic($duplicateMigration['state']==='FAIL'&&$duplicateMigration['record_count']===2&&$duplicateMigration['duplicate_count']===1&&$duplicateMigration['batches']===[1,2],'duplicate migration ledger rows retained');
$missingColumn=array_values(array_filter($one['expected_column_reconciliation'],fn($r)=>$r['table']==='source_snapshot_reconciliation_requests'&&$r['column']==='expected_previous_snapshot_id'))[0];
$missingForeignKey=array_values(array_filter($one['expected_foreign_key_reconciliation'],fn($r)=>$r['name']==='ssr_source_fk'))[0];
$missingTriggerName='audit_immut_'.substr(hash('sha256','verification_decisions'),0,16).'_u';
$missingTrigger=array_values(array_filter($one['expected_trigger_reconciliation'],fn($r)=>$r['name']===$missingTriggerName))[0];
$missingMigration=array_values(array_filter($one['migration_ledger_reconciliation'],fn($r)=>$r['migration']===$expectedMigrations[1]))[0];
assertDiagnostic($missingColumn['state']==='MISSING'&&$missingForeignKey['state']==='MISSING'&&$missingTrigger['state']==='MISSING'&&$missingMigration['state']==='MISSING','absent expected objects are MISSING with complete visibility');
$badIndexes=array_values(array_filter($one['expected_index_reconciliation'],fn($r)=>$r['state']!=='PASS'));
assertDiagnostic($badIndexes===[],'index definition mismatch: '.implode(',',array_map(fn($r)=>$r['name'].':'.$r['state'], $badIndexes)));

/* NULL-safe nullable-unique and open-only duplicate regression fixtures. */
$pdo->exec('INSERT INTO source_retrieval_runs (idempotency_key) VALUES (NULL),(NULL)');
$pdo->exec('INSERT INTO source_snapshots (capture_key) VALUES (NULL),(NULL)');
$nullDuplicateEvidence = collectDiagnostic($pdo);
foreach (['duplicate_source_retrieval_idempotency_key','duplicate_source_snapshot_capture_key'] as $checkId) {
    $check = array_values(array_filter($nullDuplicateEvidence['duplicate_checks'], static fn (array $row): bool => $row['id'] === $checkId))[0];
    assertDiagnostic($check['state'] === 'PASS', $checkId.' treats multiple NULL values as non-duplicates');
}

$pdo->exec('ALTER TABLE source_retrieval_runs DROP INDEX source_retrieval_runs_idempotency_key_unique');
$pdo->exec("INSERT INTO source_retrieval_runs (idempotency_key) VALUES ('phase1d2a-duplicate'),('phase1d2a-duplicate')");
$nonNullDuplicateEvidence = collectDiagnostic($pdo);
$nonNullDuplicate = array_values(array_filter($nonNullDuplicateEvidence['duplicate_checks'], static fn (array $row): bool => $row['id'] === 'duplicate_source_retrieval_idempotency_key'))[0];
assertDiagnostic($nonNullDuplicate['state'] === 'FAIL', 'duplicate non-NULL unique values are rejected');
$pdo->exec("DELETE FROM source_retrieval_runs WHERE idempotency_key='phase1d2a-duplicate'");
$pdo->exec('CREATE UNIQUE INDEX source_retrieval_runs_idempotency_key_unique ON source_retrieval_runs (idempotency_key)');

$pdo->exec("INSERT INTO data_reverification_queue (subject_type,subject_id,attribute,status) VALUES ('phase1d2a-fixture',987654,'identity','open'),('phase1d2a-fixture',987654,'identity','closed')");
$openOnlyQueueEvidence = collectDiagnostic($pdo);
$openQueueCheck = array_values(array_filter($openOnlyQueueEvidence['duplicate_checks'], static fn (array $row): bool => $row['id'] === 'duplicate_open_reverification_identity'))[0];
assertDiagnostic($openQueueCheck['state'] === 'PASS', 'closed reverification record is excluded from open identity grouping');
$pdo->exec("INSERT INTO data_reverification_queue (subject_type,subject_id,attribute,status) VALUES ('phase1d2a-fixture',987654,'identity','open')");
$openDuplicateQueueEvidence = collectDiagnostic($pdo);
$openQueueDuplicate = array_values(array_filter($openDuplicateQueueEvidence['duplicate_checks'], static fn (array $row): bool => $row['id'] === 'duplicate_open_reverification_identity'))[0];
assertDiagnostic($openQueueDuplicate['state'] === 'FAIL', 'duplicate open reverification identities are rejected');
$pdo->exec("DELETE FROM data_reverification_queue WHERE subject_type='phase1d2a-fixture'");

$pdo->exec("INSERT INTO monitoring_alerts (dedupe_key,status) VALUES ('phase1d2a-closed-alert','closed'),('phase1d2a-closed-alert','closed')");
$closedAlertEvidence = collectDiagnostic($pdo);
$closedAlertCheck = array_values(array_filter($closedAlertEvidence['duplicate_checks'], static fn (array $row): bool => $row['id'] === 'duplicate_open_monitoring_alert_dedupe_key'))[0];
assertDiagnostic($closedAlertCheck['state'] === 'PASS', 'closed monitoring alerts are excluded from open dedupe grouping');
$pdo->exec("INSERT INTO monitoring_alerts (dedupe_key,status) VALUES ('phase1d2a-open-alert','open'),('phase1d2a-open-alert','open')");
$openAlertEvidence = collectDiagnostic($pdo);
$openAlertCheck = array_values(array_filter($openAlertEvidence['duplicate_checks'], static fn (array $row): bool => $row['id'] === 'duplicate_open_monitoring_alert_dedupe_key'))[0];
assertDiagnostic($openAlertCheck['state'] === 'FAIL', 'duplicate open monitoring alert dedupe keys are rejected');
$pdo->exec("DELETE FROM monitoring_alerts WHERE dedupe_key IN ('phase1d2a-closed-alert','phase1d2a-open-alert')");

if(getenv('EVIDENCE_OUTPUT')) file_put_contents(getenv('EVIDENCE_OUTPUT'),json_encode($one,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL);
assertDiagnostic($one['transaction']['outcome']==='ROLLED_BACK','success rollback');
foreach($one['queue_mapping']['records'] as $r) assertDiagnostic(array_diff(array_keys($r),['record_ordinal','state','mapping_match_count'])===[],'queue privacy');
foreach($one['alert_mapping']['records'] as $r) assertDiagnostic(array_diff(array_keys($r),['record_ordinal','state','mapping_match_count'])===[],'alert privacy');

// A table-scoped account cannot prove absent tables are missing; they must remain UNKNOWN.
$limitedUser='phase1c_diag_limited';
$limitedPassword=bin2hex(random_bytes(16));
$database=(string)$pdo->query('SELECT DATABASE()')->fetchColumn();
$pdo->exec("CREATE USER '".$limitedUser."'@'%' IDENTIFIED BY '".$limitedPassword."'");
$pdo->exec('GRANT SELECT ON '.$database.'.source_retrieval_runs TO '.$limitedUser."@'%'");
$pdo->exec('GRANT SELECT ON '.$database.'.source_snapshot_reconciliation_requests TO '.$limitedUser."@'%'");
$pdo->exec('GRANT SELECT ON '.$database.'.security_audit_events TO '.$limitedUser."@'%'");
$pdo->exec('GRANT SELECT ON '.$database.'.verification_decisions TO '.$limitedUser."@'%'");
$pdo->exec('GRANT SELECT ON '.$database.'.migrations TO '.$limitedUser."@'%'");
$limitedPdo=new PDO('mysql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT')?:3306).';dbname='.getenv('DB_DATABASE').';charset=utf8mb4',$limitedUser,$limitedPassword,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$limited=collectDiagnostic($limitedPdo);
assertDiagnostic($limited['metadata_visibility']['complete']===false,'limited grant detected');
foreach($limited['tables'] as $t) if($t['state']==='MISSING') throw new RuntimeException('limited metadata must not be classified MISSING');
$limitedColumn=array_values(array_filter($limited['expected_column_reconciliation'],fn($r)=>$r['table']==='source_snapshot_reconciliation_requests'&&$r['column']==='expected_previous_snapshot_id'))[0];
$limitedForeignKey=array_values(array_filter($limited['expected_foreign_key_reconciliation'],fn($r)=>$r['name']==='ssr_source_fk'))[0];
$limitedTrigger=array_values(array_filter($limited['expected_trigger_reconciliation'],fn($r)=>$r['name']===$missingTriggerName))[0];
$limitedMigration=array_values(array_filter($limited['migration_ledger_reconciliation'],fn($r)=>$r['migration']===$expectedMigrations[1]))[0];
assertDiagnostic($limitedColumn['state']==='UNKNOWN'&&$limitedForeignKey['state']==='UNKNOWN'&&$limitedTrigger['state']==='UNKNOWN'&&$limitedMigration['state']==='UNKNOWN','incomplete metadata remains UNKNOWN');
$pdo->exec("DROP USER '".$limitedUser."'@'%'");


/* Nullable optional foreign-key values are valid; non-NULL missing parents fail. */
$pdo->exec('INSERT INTO source_monitoring_policies (id) VALUES (NULL)');
$policyId = (int) $pdo->lastInsertId();
$pdo->exec('INSERT INTO sources (id) VALUES (NULL)');
$sourceId = (int) $pdo->lastInsertId();
$runInsert = $pdo->prepare('INSERT INTO monitoring_policy_runs (source_monitoring_policy_id,execution_run_id,started_at) VALUES (?,NULL,CURRENT_TIMESTAMP)');
$runInsert->execute([$policyId]);
$monitoringRunId = (int) $pdo->lastInsertId();
$sourceRunInsert = $pdo->prepare('INSERT INTO monitoring_policy_run_sources (monitoring_policy_run_id,source_id,retrieval_run_id,observation_id,status) VALUES (?,?,NULL,NULL,?)');
$sourceRunInsert->execute([$monitoringRunId,$sourceId,'complete']);
$sourceRunId = (int) $pdo->lastInsertId();
$pdo->exec("INSERT INTO monitoring_alert_identities (dedupe_key,active_alert_id) VALUES ('phase1d2a-null-active',NULL)");
$nullOrphanEvidence = collectDiagnostic($pdo);
$nullableOrphanIds = [
    'orphan_monitoring_runs_without_governed_run',
    'orphan_monitoring_sources_without_retrieval_run',
    'orphan_monitoring_sources_without_observation',
    'orphan_monitoring_alert_identities_without_active_alert',
];
foreach ($nullableOrphanIds as $checkId) {
    $check = array_values(array_filter($nullOrphanEvidence['orphan_checks'], static fn (array $row): bool => $row['id'] === $checkId))[0];
    assertDiagnostic($check['state'] === 'PASS', $checkId.' accepts a valid NULL optional reference');
}
$missingRunId = str_pad('phase1d2a-orphan-run', 36, 'x');
$pdo->prepare('UPDATE monitoring_policy_runs SET execution_run_id=? WHERE id=?')->execute([$missingRunId,$monitoringRunId]);
$pdo->prepare('UPDATE monitoring_policy_run_sources SET retrieval_run_id=999999,observation_id=999999 WHERE id=?')->execute([$sourceRunId]);
$pdo->exec("UPDATE monitoring_alert_identities SET active_alert_id=999999 WHERE dedupe_key='phase1d2a-null-active'");
$nonNullOrphanEvidence = collectDiagnostic($pdo);
foreach ($nullableOrphanIds as $checkId) {
    $check = array_values(array_filter($nonNullOrphanEvidence['orphan_checks'], static fn (array $row): bool => $row['id'] === $checkId))[0];
    assertDiagnostic($check['state'] === 'FAIL', $checkId.' rejects a non-NULL reference with no parent');
}


$pdo->exec('ALTER TABLE source_retrieval_runs DROP INDEX source_retrieval_runs_idempotency_key_unique');
$pdo->exec('ALTER TABLE source_retrieval_runs ADD COLUMN wrong_column INT NULL');
$pdo->exec('CREATE INDEX source_retrieval_runs_idempotency_key_unique ON source_retrieval_runs(wrong_column)');
$mismatch=collectDiagnostic($pdo);
$idx=array_values(array_filter($mismatch['expected_index_reconciliation'],fn($r)=>$r['name']==='source_retrieval_runs_idempotency_key_unique'))[0];
assertDiagnostic($idx['state']==='FAIL','index definition mismatch');

$pdo->exec('ALTER TABLE source_retrieval_runs DROP INDEX source_retrieval_runs_idempotency_key_unique');
$pdo->exec('ALTER TABLE source_retrieval_runs DROP COLUMN idempotency_key');
$pdo->exec('DROP TABLE source_snapshot_reconciliation_requests');
$missing=collectDiagnostic($pdo);
$dup=array_values(array_filter($missing['duplicate_checks'],fn($r)=>$r['id']==='duplicate_source_retrieval_idempotency_key'))[0];
$table=array_values(array_filter($missing['tables'],fn($r)=>$r['table']==='source_snapshot_reconciliation_requests'))[0];
assertDiagnostic($dup['state']==='NOT_CHECKED','missing-column prerequisite');
assertDiagnostic($table['state']==='MISSING','missing-table classification');
$pdo->exec('DROP TABLE source_retrieval_runs');
$missingOrphanPrerequisite = collectDiagnostic($pdo);
$missingRetrievalOrphan = array_values(array_filter($missingOrphanPrerequisite['orphan_checks'], static fn (array $row): bool => $row['id'] === 'orphan_monitoring_sources_without_retrieval_run'))[0];
assertDiagnostic($missingRetrievalOrphan['state'] === 'NOT_CHECKED', 'missing orphan parent table remains NOT_CHECKED');

// Inject a metadata permission/query failure after beginTransaction and prove rollback.
class Phase1CFailingPDO extends PDO {
    public bool $active=false; public bool $rolledBack=false;
    public function __construct() {}
    public function exec(string $statement): int|false { return 0; }
    public function beginTransaction(): bool { $this->active=true; return true; }
    public function query(string $query, ?int $fetchMode=null, mixed ...$fetchModeArgs): PDOStatement|false { throw new PDOException('sensitive-message-must-not-be-logged'); }
    public function inTransaction(): bool { return $this->active; }
    public function rollBack(): bool { $this->active=false;$this->rolledBack=true;return true; }
}
$fault=new Phase1CFailingPDO();
try { collectDiagnostic($fault); throw new RuntimeException('failure path was not exercised'); }
catch(PDOException $e) { assertDiagnostic($fault->rolledBack && !$fault->active,'failure rollback'); }

// Regression: source rows may exist while their identity-mapping tables are absent.
// Laravel converts PHP warnings (including undefined variables) into ErrorException;
// emulate that behavior so this path cannot silently pass in disposable CI.
$pdo->exec('DROP TABLE data_reverification_queue_identities');
$pdo->exec('DROP TABLE monitoring_alert_identities');
set_error_handler(static function(int $severity,string $message,string $file,int $line): bool {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException('PHP warning converted to exception',0,$severity,$file,$line);
});
try {
    $unmapped=collectDiagnostic($pdo);
} finally {
    restore_error_handler();
}
assertDiagnostic($unmapped['queue_mapping']['state']==='NOT_CHECKED','missing queue identity table is safely NOT_CHECKED');
assertDiagnostic($unmapped['alert_mapping']['state']==='NOT_CHECKED','missing alert identity table is safely NOT_CHECKED');
foreach($unmapped['queue_mapping']['records'] as $record) assertDiagnostic($record['state']==='NOT_CHECKED','queue mapping record remains safely NOT_CHECKED');
foreach($unmapped['alert_mapping']['records'] as $record) assertDiagnostic($record['state']==='NOT_CHECKED','alert mapping record remains safely NOT_CHECKED');

echo "diagnostic_acceptance=PASS engine=".$pdo->query('SELECT VERSION()')->fetchColumn()." tables=39 indexes=23 orphans=18 duplicates=14 queue=5 alerts=2 deterministic=yes rollback=success_and_failure\n";

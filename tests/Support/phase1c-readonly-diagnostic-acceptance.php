<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/scripts/phase1c/readonly-schema-diagnostic.php';

function assertDiagnostic(bool $ok,string $message): void { if(!$ok) throw new RuntimeException('acceptance_failed: '.$message); }
function collectDiagnostic(PDO $pdo): array { return (new Phase1CReadOnlyDiagnostic($pdo))->collect(); }

$pdo=new PDO('mysql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT')?:3306).';dbname='.getenv('DB_DATABASE').';charset=utf8mb4',getenv('DB_USERNAME'),getenv('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
$tables=['assertions','change_events','data_reverification_queue','data_reverification_queue_identities','enrichment_import_rows','evidence','governed_execution_leases','governed_execution_run_events','governed_execution_runs','identity_match_candidates','identity_resolution_decisions','import_batches','import_conflict_events','import_conflicts','import_promotion_decisions','import_promotion_events','import_reconciliation_events','import_rows','institute_discovery_run_outcomes','institutions','migrations','monitoring_alert_identities','monitoring_alerts','monitoring_policy_run_sources','monitoring_policy_runs','official_source_refresh_events','password_reset_tokens','provenance_review_events','reverification_events','security_audit_events','source_change_observations','source_monitoring_policies','source_retrieval_runs','source_snapshot_reconciliation_requests','source_snapshots','sources','structured_enrichment_promotion_events','users','verification_decisions'];
foreach($tables as $t) $pdo->exec('CREATE TABLE '.$t.' (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY) ENGINE=InnoDB');

$columns=[
'identity_resolution_decisions'=>['import_row_id BIGINT UNSIGNED NULL','decision_identity_key CHAR(64) NULL'],
'security_audit_events'=>['monitoring_idempotency_key VARCHAR(191) NULL'],
'verification_decisions'=>['assertion_id BIGINT UNSIGNED NULL'],
'import_reconciliation_events'=>['import_batch_id BIGINT UNSIGNED NULL'],
'import_promotion_decisions'=>['import_row_id BIGINT UNSIGNED NULL'],
'import_promotion_events'=>['import_row_id BIGINT UNSIGNED NULL','import_batch_id BIGINT UNSIGNED NULL'],
'reverification_events'=>['queue_id BIGINT UNSIGNED NULL'],
'structured_enrichment_promotion_events'=>['enrichment_import_row_id BIGINT UNSIGNED NULL','import_batch_id BIGINT UNSIGNED NULL'],
'import_conflict_events'=>['import_conflict_id BIGINT UNSIGNED NULL','idempotency_key CHAR(64) NULL','occurred_at TIMESTAMP NULL'],
'governed_execution_run_events'=>['run_id CHAR(36) NULL'],
'monitoring_policy_runs'=>['source_monitoring_policy_id BIGINT UNSIGNED NULL','execution_run_id CHAR(36) NULL'],
'monitoring_policy_run_sources'=>['monitoring_policy_run_id BIGINT UNSIGNED NULL','source_id BIGINT UNSIGNED NULL','retrieval_run_id BIGINT UNSIGNED NULL','observation_id BIGINT UNSIGNED NULL','status VARCHAR(20) NULL'],
'monitoring_alert_identities'=>['dedupe_key VARCHAR(40) NULL','active_alert_id BIGINT UNSIGNED NULL'],
'monitoring_alerts'=>['dedupe_key VARCHAR(40) NULL','status VARCHAR(20) NULL'],
'data_reverification_queue'=>['subject_type VARCHAR(255) NULL','subject_id BIGINT UNSIGNED NULL','attribute VARCHAR(255) NULL','status VARCHAR(20) NULL'],
'data_reverification_queue_identities'=>['identity_key VARCHAR(64) NULL','subject_type VARCHAR(255) NULL','subject_id BIGINT UNSIGNED NULL','attribute VARCHAR(255) NULL','active_queue_id BIGINT UNSIGNED NULL'],
'source_retrieval_runs'=>['idempotency_key VARCHAR(64) NULL'],
'source_snapshots'=>['capture_key VARCHAR(64) NULL'],
'source_change_observations'=>['current_snapshot_id BIGINT UNSIGNED NULL'],
'institute_discovery_run_outcomes'=>['idempotency_key VARCHAR(64) NULL'],
'change_events'=>['source_change_assertion_link_id BIGINT UNSIGNED NULL'],
'import_batches'=>['discovery_identity_key VARCHAR(64) NULL'],
'evidence'=>['discovery_identity_key VARCHAR(64) NULL'],
'import_rows'=>['discovery_identity_key VARCHAR(64) NULL'],
'assertions'=>['discovery_identity_key VARCHAR(64) NULL'],
'identity_match_candidates'=>['identity_key CHAR(64) NULL'],
'import_conflicts'=>['identity_key CHAR(64) NULL'],
'source_snapshot_reconciliation_requests'=>['identity_key VARCHAR(64) NULL','source_id BIGINT UNSIGNED NULL','status VARCHAR(20) NULL','current_snapshot_id BIGINT UNSIGNED NULL'],
'governed_execution_leases'=>['task_key VARCHAR(120) NULL','expires_at TIMESTAMP NULL'],
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
$before=$rowCounts();$beforeSchema=$schemaCounts();$one=collectDiagnostic($pdo);$two=collectDiagnostic($pdo);
assertDiagnostic(json_encode($one,JSON_UNESCAPED_SLASHES)===json_encode($two,JSON_UNESCAPED_SLASHES),'deterministic evidence');
assertDiagnostic($one['registry_counts']['tables']===39 && $one['registry_counts']['expected_indexes']===23 && $one['registry_counts']['orphan_checks']===18 && $one['registry_counts']['duplicate_checks']===14,'registry sizes');
assertDiagnostic(count($one['tables'])===39 && count($one['expected_index_reconciliation'])===23,'complete table/index metadata');
assertDiagnostic(count($one['orphan_checks'])===18 && count($one['duplicate_checks'])===14,'individual check reporting');
assertDiagnostic($one['queue_mapping']['state']==='PASS' && count($one['queue_mapping']['records'])===5,'five sanitized queue mappings');
assertDiagnostic($one['alert_mapping']['state']==='PASS' && count($one['alert_mapping']['records'])===2,'two sanitized alert mappings');

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
assertDiagnostic($one['orphan_checks'][1]['state']==='FAIL','orphan failure');
$badIndexes=array_values(array_filter($one['expected_index_reconciliation'],fn($r)=>$r['state']!=='PASS'));
assertDiagnostic($badIndexes===[],'index definition mismatch: '.implode(',',array_map(fn($r)=>$r['name'].':'.$r['state'], $badIndexes)));
if(getenv('EVIDENCE_OUTPUT')) file_put_contents(getenv('EVIDENCE_OUTPUT'),json_encode($one,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL);
assertDiagnostic($one['transaction']['outcome']==='ROLLED_BACK','success rollback');
assertDiagnostic($before===$rowCounts(),'collector caused no row changes');
assertDiagnostic($beforeSchema===$schemaCounts(),'collector caused no schema-object changes');
foreach($one['queue_mapping']['records'] as $r) assertDiagnostic(array_diff(array_keys($r),['record_ordinal','state','mapping_match_count'])===[],'queue privacy');
foreach($one['alert_mapping']['records'] as $r) assertDiagnostic(array_diff(array_keys($r),['record_ordinal','state','mapping_match_count'])===[],'alert privacy');

// A table-scoped account cannot prove absent tables are missing; they must remain UNKNOWN.
$limitedUser='phase1c_diag_limited';
$limitedPassword=bin2hex(random_bytes(16));
$database=(string)$pdo->query('SELECT DATABASE()')->fetchColumn();
$pdo->exec("CREATE USER '".$limitedUser."'@'%' IDENTIFIED BY '".$limitedPassword."'");
$pdo->exec('GRANT SELECT ON '.$database.'.source_retrieval_runs TO '.$limitedUser."@'%'");
$limitedPdo=new PDO('mysql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT')?:3306).';dbname='.getenv('DB_DATABASE').';charset=utf8mb4',$limitedUser,$limitedPassword,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$limited=collectDiagnostic($limitedPdo);
assertDiagnostic($limited['metadata_visibility']['complete']===false,'limited grant detected');
foreach($limited['tables'] as $t) if($t['state']==='MISSING') throw new RuntimeException('limited metadata must not be classified MISSING');
$pdo->exec("DROP USER '".$limitedUser."'@'%'");

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

echo "diagnostic_acceptance=PASS engine=".$pdo->query('SELECT VERSION()')->fetchColumn()." tables=39 indexes=23 orphans=18 duplicates=14 queue=5 alerts=2 deterministic=yes rollback=success_and_failure\n";

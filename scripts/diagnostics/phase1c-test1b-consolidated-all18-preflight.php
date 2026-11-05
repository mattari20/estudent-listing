<?php
declare(strict_types=1);

/**
 * Phase 1C Test 1B consolidated read-only preflight for all 18 pending migrations.
 * Aggregate counts and information_schema only; no row payloads, migrations,
 * DDL, DML, application writes, temporary files, or persistent reports.
 */
require getcwd().'/vendor/autoload.php';

$cache = getcwd().'/bootstrap/cache/config.php';
if (is_file($cache)) {
    $app = require $cache;
    if (!is_array($app) || !is_array($app['database'] ?? null)) {
        fwrite(STDERR, "laravel_config_cache=INVALID\n"); exit(10);
    }
    $dbConfig = $app['database']; $configSource = 'laravel_config_cache';
} else {
    if (!class_exists(\Dotenv\Dotenv::class)) { fwrite(STDERR, "dotenv_library=UNAVAILABLE\n"); exit(10); }
    \Dotenv\Dotenv::createImmutable(getcwd())->safeLoad();
    $dbConfig = require getcwd().'/config/database.php'; $configSource = 'dotenv_plus_config_database';
}
$name = (string)($dbConfig['default'] ?? 'mysql');
$conn = $dbConfig['connections'][$name] ?? null;
if (!is_array($conn) || ($conn['driver'] ?? null) !== 'mysql') { fwrite(STDERR, "effective_database_driver=UNSUPPORTED_OR_MISSING\n"); exit(11); }
if (!empty($conn['url'])) {
    $parser = \Illuminate\Database\ConfigurationUrlParser::class;
    if (!class_exists($parser)) { fwrite(STDERR, "laravel_database_url_parser=UNAVAILABLE\n"); exit(12); }
    $conn = (new $parser())->parseConfiguration($conn);
}
$host=(string)($conn['host']??''); $db=(string)($conn['database']??'');
$user=(string)($conn['username']??''); $pass=(string)($conn['password']??'');
$port=(string)($conn['port']??'3306'); $socket=(string)($conn['unix_socket']??'');
if ($db==='' || $user==='' || (!$socket && ($host==='' || !ctype_digit($port)))) { fwrite(STDERR, "effective_database_connection=INCOMPLETE\n"); exit(13); }

$pdo=null; $open=false;
try {
    $dsn=$socket!=='' ? 'mysql:unix_socket='.$socket.';dbname='.$db.';charset=utf8mb4' : 'mysql:host='.$host.';port='.$port.';dbname='.$db.';charset=utf8mb4';
    $pdo=new PDO($dsn,$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>10]);
    $pdo->exec('SET TRANSACTION READ ONLY'); $pdo->beginTransaction(); $open=true;
    $identity=$pdo->query('SELECT DATABASE() AS d, @@hostname AS h, CURRENT_USER() AS u')->fetch();
    $fingerprint=hash('sha256',implode("\0",[strtolower((string)($identity['h']??'')),strtolower((string)($identity['d']??'')),strtolower((string)($identity['u']??''))]));
    echo "phase_1c_test_1b_consolidated_preflight=STARTED\n";
    echo "effective_database_config_source={$configSource}\n";
    echo "effective_database_connection_name={$name}\n";
    echo "effective_database_fingerprint={$fingerprint}\n";
    echo "inspection_scope=aggregate_counts_and_information_schema_only\n";

    $tables=[
      'migrations','users','password_reset_tokens','import_batches','source_retrieval_runs','source_snapshots','source_change_observations',
      'institute_discovery_run_outcomes','change_events','data_reverification_queue','data_reverification_queue_identities',
      'monitoring_alerts','monitoring_alert_identities','security_audit_events','source_monitoring_policies','monitoring_policy_runs',
      'monitoring_policy_run_sources','governed_execution_leases','governed_execution_runs','governed_execution_run_events',
      'sources','verification_decisions','provenance_review_events','import_promotion_decisions','import_promotion_events',
      'reverification_events','structured_enrichment_promotion_events','official_source_refresh_events','import_conflict_events',
      'import_reconciliation_events','source_snapshot_reconciliation_requests','identity_resolution_decisions','import_rows',
      'assertions','evidence','enrichment_import_rows','import_conflicts','identity_match_candidates'
    ];
    $tableStmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $present=[];
    foreach($tables as $t){$tableStmt->execute([$t]);if((int)$tableStmt->fetchColumn()===1)$present[$t]=true;}
    echo 'table_inventory_checked='.count($tables).';present='.count($present).';missing='.(count($tables)-count($present))."\n";
    foreach($tables as $t) if(!isset($present[$t])) echo "table_{$t}=MISSING\n";

    $columnStmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $hasColumn=static function(string $t,string $c)use($columnStmt):bool{$columnStmt->execute([$t,$c]);return (int)$columnStmt->fetchColumn()===1;};
    $indexStmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?');
    $hasIndex=static function(string $t,string $i)use($indexStmt):bool{$indexStmt->execute([$t,$i]);return (int)$indexStmt->fetchColumn()>0;};

    $migrations=[
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
      '2026_11_04_000015_add_monitoring_checkpoint_foreign_keys_and_heartbeat'
    ];
    $ledgerStmt=isset($present['migrations'])?$pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration=?'):null;
    $pending=0;
    foreach($migrations as $m){if(!$ledgerStmt){echo "ledger_{$m}=NOT_CHECKED_LEDGER_MISSING\n";continue;}$ledgerStmt->execute([$m]);$n=(int)$ledgerStmt->fetchColumn();if($n===0)$pending++;echo "ledger_{$m}={$n}\n";}
    echo 'pending_migration_count_among_18='.$pending."\n";

    // Expected columns and named indexes for pending migrations; each item is gated.
    $expectedColumns=[
      ['password_reset_tokens','email'],['password_reset_tokens','token'],['password_reset_tokens','created_at'],
      ['users','must_change_password'],['import_batches','duplicate_rows'],
      ['structured_enrichment_promotion_events','enrichment_import_row_id'],['structured_enrichment_promotion_events','import_batch_id'],['structured_enrichment_promotion_events','occurred_at'],
      ['governed_execution_leases','task_key'],['governed_execution_leases','owner_token'],['governed_execution_leases','run_id'],['governed_execution_leases','expires_at'],
      ['governed_execution_runs','id'],['governed_execution_run_events','run_id'],['governed_execution_run_events','event_type'],['governed_execution_run_events','occurred_at'],
      ['source_retrieval_runs','source_retrieval_runs_idempotency_key_unique'],['source_snapshots','source_snapshots_capture_key_unique'],['source_change_observations','current_snapshot_id'],
      ['institute_discovery_run_outcomes','institute_discovery_run_outcomes_idempotency_key_unique'],['change_events','source_change_assertion_link_id'],
      ['data_reverification_queue_identities','identity_key'],['data_reverification_queue_identities','subject_type'],['data_reverification_queue_identities','subject_id'],['data_reverification_queue_identities','active_queue_id'],['monitoring_alert_identities','dedupe_key'],['monitoring_alert_identities','active_alert_id'],
      ['security_audit_events','security_audit_events_monitoring_idempotency_key_unique'],['monitoring_policy_runs','heartbeat_at'],
      ['monitoring_policy_runs','source_monitoring_policy_id'],['monitoring_policy_runs','execution_run_id'],['monitoring_policy_runs','started_at'],['monitoring_policy_run_sources','monitoring_policy_run_id'],['monitoring_policy_run_sources','source_id'],['monitoring_policy_run_sources','retrieval_run_id'],['monitoring_policy_run_sources','observation_id'],['import_batches','discovery_identity_key'],
      ['evidence','discovery_identity_key'],['import_rows','discovery_identity_key'],['assertions','discovery_identity_key'],
      ['identity_match_candidates','identity_key'],['identity_resolution_decisions','decision_identity_key'],
      ['import_conflicts','identity_key'],['import_conflict_events','idempotency_key'],
      ['source_snapshot_reconciliation_requests','source_snapshot_reconciliation_requests_identity_key_unique']
    ];
    $colMissing=0;
    foreach($expectedColumns as [$t,$c]){if(!isset($present[$t])){echo "expected_column_{$t}_{$c}=NOT_CHECKED_TABLE_MISSING\n";continue;}$ok=$hasColumn($t,$c);if(!$ok)$colMissing++;echo "expected_column_{$t}_{$c}=".($ok?'PRESENT':'MISSING')."\n";}
    echo 'expected_column_checks='.count($expectedColumns).';missing='.$colMissing."\n";

    $expectedIndexes=[
      ['password_reset_tokens','PRIMARY'],['governed_execution_leases','governed_execution_leases_task_key_unique'],['governed_execution_leases','governed_execution_leases_expires_at_index'],
      ['source_retrieval_runs','idempotency_key'],['source_snapshots','capture_key'],
      ['source_change_observations','source_observations_current_snapshot_unique'],
      ['institute_discovery_run_outcomes','idempotency_key'],['change_events','change_events_source_link_unique'],
      ['data_reverification_queue_identities','PRIMARY'],['monitoring_alert_identities','PRIMARY'],
      ['security_audit_events','monitoring_idempotency_key'],['monitoring_policy_run_sources','monitoring_policy_run_source_unique'],
      ['import_batches','import_batches_discovery_identity_unique'],['evidence','evidence_discovery_identity_unique'],
      ['import_rows','import_rows_discovery_identity_unique'],['assertions','assertions_discovery_identity_unique'],
      ['identity_match_candidates','identity_match_candidates_identity_key_unique'],
      ['identity_resolution_decisions','identity_resolution_decisions_identity_key_unique'],
      ['import_conflicts','import_conflicts_identity_key_unique'],['import_conflict_events','import_conflict_events_idempotency_key_unique'],
      ['source_snapshot_reconciliation_requests','identity_key'],['source_snapshot_reconciliation_requests','source_id'],['source_snapshot_reconciliation_requests','triggering_snapshot_id'],['source_snapshot_reconciliation_requests','current_snapshot_id'],['source_snapshot_reconciliation_requests','expected_previous_snapshot_id'],['source_snapshot_reconciliation_requests','recorded_previous_snapshot_id']
    ];
    $indexMissing=0;
    foreach($expectedIndexes as [$t,$i]){if(!isset($present[$t])){echo "expected_index_{$t}_{$i}=NOT_CHECKED_TABLE_MISSING\n";continue;}$ok=$hasIndex($t,$i);if(!$ok)$indexMissing++;echo "expected_index_{$t}_{$i}=".($ok?'PRESENT':'MISSING')."\n";}
    echo 'expected_index_checks='.count($expectedIndexes).';missing='.$indexMissing."\n";

    // Read-only duplicate preconditions. A query runs only when all required columns exist.
    $duplicateChecks=[
      '000003_duplicate_existing_retrieval_idempotency_key'=>['source_retrieval_runs',['idempotency_key'] ,"SELECT COUNT(*) FROM (SELECT idempotency_key FROM source_retrieval_runs WHERE idempotency_key IS NOT NULL GROUP BY idempotency_key HAVING COUNT(*)>1) x"],
      '000004_duplicate_current_snapshot_id'=>['source_change_observations',['current_snapshot_id'],"SELECT COUNT(*) FROM (SELECT current_snapshot_id FROM source_change_observations GROUP BY current_snapshot_id HAVING COUNT(*)>1) x"],
      '000005_duplicate_existing_discovery_outcome_idempotency_key'=>['institute_discovery_run_outcomes',['idempotency_key'],"SELECT COUNT(*) FROM (SELECT idempotency_key FROM institute_discovery_run_outcomes WHERE idempotency_key IS NOT NULL GROUP BY idempotency_key HAVING COUNT(*)>1) x"],
      '000006_duplicate_existing_change_event_source_link'=>['change_events',['source_change_assertion_link_id'],"SELECT COUNT(*) FROM (SELECT source_change_assertion_link_id FROM change_events WHERE source_change_assertion_link_id IS NOT NULL GROUP BY source_change_assertion_link_id HAVING COUNT(*)>1) x"],
      '000007_duplicate_open_reverification_identity'=>['data_reverification_queue',['status','subject_type','subject_id','attribute'],"SELECT COUNT(*) FROM (SELECT subject_type,subject_id,attribute FROM data_reverification_queue WHERE status='open' GROUP BY subject_type,subject_id,attribute HAVING COUNT(*)>1) x"],
      '000009_duplicate_open_monitoring_dedupe_key'=>['monitoring_alerts',['status','dedupe_key'],"SELECT COUNT(*) FROM (SELECT dedupe_key FROM monitoring_alerts WHERE status='open' GROUP BY dedupe_key HAVING COUNT(*)>1) x"],
      '000012_duplicate_conflict_event_idempotency_key'=>['import_conflict_events',['idempotency_key'],"SELECT COUNT(*) FROM (SELECT idempotency_key FROM import_conflict_events WHERE idempotency_key IS NOT NULL GROUP BY idempotency_key HAVING COUNT(*)>1) x"],
      '000012_duplicate_identity_match_key'=>['identity_match_candidates',['identity_key'],"SELECT COUNT(*) FROM (SELECT identity_key FROM identity_match_candidates WHERE identity_key IS NOT NULL GROUP BY identity_key HAVING COUNT(*)>1) x"],
      '000012_duplicate_decision_identity_key'=>['identity_resolution_decisions',['decision_identity_key'],"SELECT COUNT(*) FROM (SELECT decision_identity_key FROM identity_resolution_decisions WHERE decision_identity_key IS NOT NULL GROUP BY decision_identity_key HAVING COUNT(*)>1) x"],
      '000012_duplicate_import_conflict_identity_key'=>['import_conflicts',['identity_key'],"SELECT COUNT(*) FROM (SELECT identity_key FROM import_conflicts WHERE identity_key IS NOT NULL GROUP BY identity_key HAVING COUNT(*)>1) x"],
      '000011_duplicate_import_batch_discovery_key'=>['import_batches',['discovery_identity_key'],"SELECT COUNT(*) FROM (SELECT discovery_identity_key FROM import_batches WHERE discovery_identity_key IS NOT NULL GROUP BY discovery_identity_key HAVING COUNT(*)>1) x"],
      '000011_duplicate_evidence_discovery_key'=>['evidence',['discovery_identity_key'],"SELECT COUNT(*) FROM (SELECT discovery_identity_key FROM evidence WHERE discovery_identity_key IS NOT NULL GROUP BY discovery_identity_key HAVING COUNT(*)>1) x"],
      '000011_duplicate_import_row_discovery_key'=>['import_rows',['discovery_identity_key'],"SELECT COUNT(*) FROM (SELECT discovery_identity_key FROM import_rows WHERE discovery_identity_key IS NOT NULL GROUP BY discovery_identity_key HAVING COUNT(*)>1) x"],
      '000011_duplicate_assertion_discovery_key'=>['assertions',['discovery_identity_key'],"SELECT COUNT(*) FROM (SELECT discovery_identity_key FROM assertions WHERE discovery_identity_key IS NOT NULL GROUP BY discovery_identity_key HAVING COUNT(*)>1) x"]
    ];
    $duplicateFailures=0;
    foreach($duplicateChecks as $key=>[$t,$cols,$sql]){
      $ready=isset($present[$t]);foreach($cols as $c)$ready=$ready&&$hasColumn($t,$c);
      if(!$ready){echo "duplicate_check_{$key}=NOT_CHECKED_PREREQUISITE_MISSING\n";continue;}
      $n=(int)$pdo->query($sql)->fetchColumn();if($n>0)$duplicateFailures++;echo "duplicate_check_{$key}={$n}\n";
    }

    // Backfill exposure: aggregate counts only, never row values.
    $backfills=[
      '000007_open_reverification_rows'=>['data_reverification_queue',['status'],"SELECT COUNT(*) FROM data_reverification_queue WHERE status='open'"],
      '000009_open_monitoring_alerts'=>['monitoring_alerts',['status'],"SELECT COUNT(*) FROM monitoring_alerts WHERE status='open'"],
      '000015_null_heartbeat_rows'=>['monitoring_policy_runs',['heartbeat_at','started_at'],"SELECT COUNT(*) FROM monitoring_policy_runs WHERE heartbeat_at IS NULL"]
    ];
    foreach($backfills as $key=>[$t,$cols,$sql]){$ready=isset($present[$t]);foreach($cols as $c)$ready=$ready&&$hasColumn($t,$c);if(!$ready){echo "backfill_exposure_{$key}=NOT_CHECKED_PREREQUISITE_MISSING\n";continue;}echo "backfill_exposure_{$key}=".(int)$pdo->query($sql)->fetchColumn()."\n";}

    // The eleven retention orphan relationships and seven monitoring relationships.
    $orphanChecks=[
      '014_identity_decisions_without_import_rows'=>['identity_resolution_decisions','import_rows',['import_row_id','id'],"SELECT COUNT(*) FROM identity_resolution_decisions d LEFT JOIN import_rows r ON r.id=d.import_row_id WHERE r.id IS NULL"],
      '014_verification_decisions_without_assertions'=>['verification_decisions','assertions',['assertion_id','id'],"SELECT COUNT(*) FROM verification_decisions d LEFT JOIN assertions a ON a.id=d.assertion_id WHERE a.id IS NULL"],
      '014_reconciliation_events_without_batches'=>['import_reconciliation_events','import_batches',['import_batch_id','id'],"SELECT COUNT(*) FROM import_reconciliation_events e LEFT JOIN import_batches b ON b.id=e.import_batch_id WHERE b.id IS NULL"],
      '014_promotion_decisions_without_rows'=>['import_promotion_decisions','import_rows',['import_row_id','id'],"SELECT COUNT(*) FROM import_promotion_decisions d LEFT JOIN import_rows r ON r.id=d.import_row_id WHERE r.id IS NULL"],
      '014_promotion_events_without_rows'=>['import_promotion_events','import_rows',['import_row_id','id'],"SELECT COUNT(*) FROM import_promotion_events e LEFT JOIN import_rows r ON r.id=e.import_row_id WHERE r.id IS NULL"],
      '014_promotion_events_without_batches'=>['import_promotion_events','import_batches',['import_batch_id','id'],"SELECT COUNT(*) FROM import_promotion_events e LEFT JOIN import_batches b ON b.id=e.import_batch_id WHERE b.id IS NULL"],
      '014_reverification_events_without_queue'=>['reverification_events','data_reverification_queue',['queue_id','id'],"SELECT COUNT(*) FROM reverification_events e LEFT JOIN data_reverification_queue q ON q.id=e.queue_id WHERE q.id IS NULL"],
      '014_enrichment_events_without_rows'=>['structured_enrichment_promotion_events','enrichment_import_rows',['enrichment_import_row_id','id'],"SELECT COUNT(*) FROM structured_enrichment_promotion_events e LEFT JOIN enrichment_import_rows r ON r.id=e.enrichment_import_row_id WHERE r.id IS NULL"],
      '014_enrichment_events_without_batches'=>['structured_enrichment_promotion_events','import_batches',['import_batch_id','id'],"SELECT COUNT(*) FROM structured_enrichment_promotion_events e LEFT JOIN import_batches b ON b.id=e.import_batch_id WHERE b.id IS NULL"],
      '014_conflict_events_without_conflicts'=>['import_conflict_events','import_conflicts',['import_conflict_id','id'],"SELECT COUNT(*) FROM import_conflict_events e LEFT JOIN import_conflicts c ON c.id=e.import_conflict_id WHERE c.id IS NULL"],
      '014_governed_events_without_runs'=>['governed_execution_run_events','governed_execution_runs',['run_id','id'],"SELECT COUNT(*) FROM governed_execution_run_events e LEFT JOIN governed_execution_runs r ON r.id=e.run_id WHERE r.id IS NULL"],
      '015_monitoring_runs_without_policies'=>['monitoring_policy_runs','source_monitoring_policies',['source_monitoring_policy_id','id'],"SELECT COUNT(*) FROM monitoring_policy_runs r LEFT JOIN source_monitoring_policies p ON p.id=r.source_monitoring_policy_id WHERE p.id IS NULL"],
      '015_monitoring_runs_without_execution_runs'=>['monitoring_policy_runs','governed_execution_runs',['execution_run_id','id'],"SELECT COUNT(*) FROM monitoring_policy_runs r LEFT JOIN governed_execution_runs g ON g.id=r.execution_run_id WHERE r.execution_run_id IS NOT NULL AND g.id IS NULL"],
      '015_monitoring_sources_without_runs'=>['monitoring_policy_run_sources','monitoring_policy_runs',['monitoring_policy_run_id','id'],"SELECT COUNT(*) FROM monitoring_policy_run_sources s LEFT JOIN monitoring_policy_runs r ON r.id=s.monitoring_policy_run_id WHERE r.id IS NULL"],
      '015_monitoring_sources_without_sources'=>['monitoring_policy_run_sources','sources',['source_id','id'],"SELECT COUNT(*) FROM monitoring_policy_run_sources s LEFT JOIN sources x ON x.id=s.source_id WHERE x.id IS NULL"],
      '015_monitoring_sources_without_retrieval_runs'=>['monitoring_policy_run_sources','source_retrieval_runs',['retrieval_run_id','id'],"SELECT COUNT(*) FROM monitoring_policy_run_sources s LEFT JOIN source_retrieval_runs r ON r.id=s.retrieval_run_id WHERE s.retrieval_run_id IS NOT NULL AND r.id IS NULL"],
      '015_monitoring_sources_without_observations'=>['monitoring_policy_run_sources','source_change_observations',['observation_id','id'],"SELECT COUNT(*) FROM monitoring_policy_run_sources s LEFT JOIN source_change_observations o ON o.id=s.observation_id WHERE s.observation_id IS NOT NULL AND o.id IS NULL"],
      '015_alert_identities_without_active_alerts'=>['monitoring_alert_identities','monitoring_alerts',['active_alert_id','id'],"SELECT COUNT(*) FROM monitoring_alert_identities i LEFT JOIN monitoring_alerts a ON a.id=i.active_alert_id WHERE i.active_alert_id IS NOT NULL AND a.id IS NULL"]
    ];
    $orphanReady=0;$orphanSkipped=0;$orphanNonzero=0;
    foreach($orphanChecks as $key=>[$child,$parent,$pairs,$sql]){
      $ready=isset($present[$child])&&isset($present[$parent]);
      if($ready){$ready=$hasColumn($child,$pairs[0])&&$hasColumn($parent,$pairs[1]);}
      if(!$ready){$orphanSkipped++;echo "orphan_check_{$key}=NOT_CHECKED_PREREQUISITE_MISSING\n";continue;}
      $n=(int)$pdo->query($sql)->fetchColumn();$orphanReady++;if($n>0)$orphanNonzero++;echo "orphan_check_{$key}={$n}\n";
    }
    echo 'orphan_checks_total='.count($orphanChecks).';evaluated='.$orphanReady.';skipped='.$orphanSkipped.';nonzero='.$orphanNonzero."\n";

    // Expected trigger and foreign-key definitions from migration source.
    $auditTables=['security_audit_events','verification_decisions','provenance_review_events','import_promotion_decisions','import_promotion_events','reverification_events','structured_enrichment_promotion_events','official_source_refresh_events','import_conflict_events','import_reconciliation_events','governed_execution_run_events'];
    $triggerNames=[];foreach($auditTables as $t){$suffix=substr(hash('sha256',$t),0,16);$triggerNames[]='audit_immut_'.$suffix.'_u';$triggerNames[]='audit_immut_'.$suffix.'_d';}
    $triggerNames[]='identity_decision_update_guard';$triggerNames[]='identity_decision_delete_guard';
    $triggerStmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME=?');
    $triggerPresent=0;foreach($triggerNames as $t){$triggerStmt->execute([$t]);if((int)$triggerStmt->fetchColumn()===1)$triggerPresent++;}
    echo 'expected_audit_triggers='.count($triggerNames).';present='.$triggerPresent.';missing='.(count($triggerNames)-$triggerPresent)."\n";

    $fkDefs=[
      ['identity_resolution_decisions','import_row_id','identity_decision_row_restrict_fk','import_rows'],
      ['verification_decisions','assertion_id','verification_decision_assertion_restrict_fk','assertions'],
      ['import_reconciliation_events','import_batch_id','import_reconciliation_batch_restrict_fk','import_batches'],
      ['import_promotion_decisions','import_row_id','import_promotion_decision_row_restrict_fk','import_rows'],
      ['import_promotion_events','import_row_id','import_promotion_event_row_restrict_fk','import_rows'],
      ['import_promotion_events','import_batch_id','import_promotion_event_batch_restrict_fk','import_batches'],
      ['reverification_events','queue_id','reverification_event_queue_restrict_fk','data_reverification_queue'],
      ['structured_enrichment_promotion_events','enrichment_import_row_id','structured_enrichment_row_restrict_fk','enrichment_import_rows'],
      ['structured_enrichment_promotion_events','import_batch_id','structured_enrichment_batch_restrict_fk','import_batches'],
      ['import_conflict_events','import_conflict_id','import_conflict_event_restrict_fk','import_conflicts'],
      ['governed_execution_run_events','run_id','governed_run_event_restrict_fk','governed_execution_runs'],
      ['source_snapshot_reconciliation_requests','source_id','ssr_source_fk','sources'],
      ['source_snapshot_reconciliation_requests','triggering_snapshot_id','ssr_triggering_snapshot_fk','source_snapshots'],
      ['source_snapshot_reconciliation_requests','current_snapshot_id','ssr_current_snapshot_fk','source_snapshots'],
      ['source_snapshot_reconciliation_requests','expected_previous_snapshot_id','ssr_expected_previous_fk','source_snapshots'],
      ['source_snapshot_reconciliation_requests','recorded_previous_snapshot_id','ssr_recorded_previous_fk','source_snapshots'],
      ['monitoring_policy_runs','source_monitoring_policy_id','mp_run_policy_fk','source_monitoring_policies'],
      ['monitoring_policy_runs','execution_run_id','mp_run_exec_fk','governed_execution_runs'],
      ['monitoring_policy_run_sources','monitoring_policy_run_id','mp_source_run_fk','monitoring_policy_runs'],
      ['monitoring_policy_run_sources','source_id','mp_source_source_fk','sources'],
      ['monitoring_policy_run_sources','retrieval_run_id','mp_source_retrieval_fk','source_retrieval_runs'],
      ['monitoring_policy_run_sources','observation_id','mp_source_observation_fk','source_change_observations'],
      ['monitoring_alert_identities','active_alert_id','mai_active_alert_fk','monitoring_alerts']
    ];
    $fkStmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? AND CONSTRAINT_NAME=? AND REFERENCED_TABLE_NAME=?');
    $fkPresent=0;foreach($fkDefs as [$t,$c,$n,$p]){$fkStmt->execute([$t,$c,$n,$p]);if((int)$fkStmt->fetchColumn()===1)$fkPresent++;}
    echo 'expected_named_foreign_keys='.count($fkDefs).';present='.$fkPresent.';missing='.(count($fkDefs)-$fkPresent)."\n";

    $pdo->rollBack();$open=false;
    echo "read_only_transaction=ROLLED_BACK\nproduction_database_changed=false\nproduction_application_files_changed=false\nproduction_migrations=NOT_RUN\nddl_dml_executed=NO\napplication_row_payloads_emitted=NO\npersistent_report_written=false\nremote_temporary_script_file_created=false\nphase_1c_test_1b_consolidated_preflight=COMPLETED\n";
} catch(Throwable $e) {
    if($open&&$pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();
    fwrite(STDERR,"phase_1c_test_1b_consolidated_preflight=ERROR\nerror_class=".get_class($e)."\nerror_message=".$e->getMessage()."\n");exit(20);
}

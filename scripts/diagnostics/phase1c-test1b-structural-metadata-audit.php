<?php
declare(strict_types=1);

/**
 * Phase 1C Test 1B structural metadata snapshot.
 * Reads information_schema only; emits schema definitions, never application row payloads.
 * No migrations, DDL, DML, application writes, or remote files.
 */
require getcwd().'/vendor/autoload.php';
$cache=getcwd().'/bootstrap/cache/config.php';
if(is_file($cache)){
  $app=require $cache;
  if(!is_array($app)||!is_array($app['database']??null)){fwrite(STDERR,"laravel_config_cache=INVALID\n");exit(10);}
  $cfg=$app['database'];$source='laravel_config_cache';
}else{
  if(!class_exists(\Dotenv\Dotenv::class)){fwrite(STDERR,"dotenv_library=UNAVAILABLE\n");exit(10);}
  \Dotenv\Dotenv::createImmutable(getcwd())->safeLoad();
  $cfg=require getcwd().'/config/database.php';$source='dotenv_plus_config_database';
}
$name=(string)($cfg['default']??'mysql');$c=$cfg['connections'][$name]??null;
if(!is_array($c)||($c['driver']??null)!=='mysql'){fwrite(STDERR,"effective_database_driver=UNSUPPORTED_OR_MISSING\n");exit(11);}
if(!empty($c['url'])){$p=\Illuminate\Database\ConfigurationUrlParser::class;if(!class_exists($p)){fwrite(STDERR,"url_parser=UNAVAILABLE\n");exit(12);}$c=(new $p())->parseConfiguration($c);}
$host=(string)($c['host']??'');$db=(string)($c['database']??'');$user=(string)($c['username']??'');$pass=(string)($c['password']??'');$port=(string)($c['port']??'3306');$socket=(string)($c['unix_socket']??'');
if($db===''||$user===''||(!$socket&&($host===''||!ctype_digit($port)))){fwrite(STDERR,"effective_database_connection=INCOMPLETE\n");exit(13);}
$tables=[
'migrations','users','password_reset_tokens','institutions','import_batches','source_retrieval_runs','source_snapshots','source_change_observations','institute_discovery_run_outcomes','change_events','data_reverification_queue','data_reverification_queue_identities','monitoring_alerts','monitoring_alert_identities','security_audit_events','source_monitoring_policies','monitoring_policy_runs','monitoring_policy_run_sources','governed_execution_leases','governed_execution_runs','governed_execution_run_events','sources','verification_decisions','provenance_review_events','import_promotion_decisions','import_promotion_events','reverification_events','structured_enrichment_promotion_events','official_source_refresh_events','import_conflict_events','import_reconciliation_events','source_snapshot_reconciliation_requests','identity_resolution_decisions','import_rows','assertions','evidence','enrichment_import_rows','import_conflicts','identity_match_candidates'
];
$pdo=null;$tx=false;
try{
 $dsn=$socket!==''?'mysql:unix_socket='.$socket.';dbname='.$db.';charset=utf8mb4':'mysql:host='.$host.';port='.$port.';dbname='.$db.';charset=utf8mb4';
 $pdo=new PDO($dsn,$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>10]);
 $pdo->exec('SET TRANSACTION READ ONLY');$pdo->beginTransaction();$tx=true;
 $id=$pdo->query('SELECT DATABASE() d,@@hostname h,CURRENT_USER() u')->fetch();
 $fp=hash('sha256',implode("\0",[strtolower((string)$id['h']),strtolower((string)$id['d']),strtolower((string)$id['u'])]));
 echo "phase_1c_test_1b_structural_audit=STARTED\nconfig_source={$source}\nconnection_name={$name}\ndatabase_fingerprint={$fp}\nmetadata_only=true\n";
 $in=implode(',',array_fill(0,count($tables),'?'));
 $q=$pdo->prepare("SELECT TABLE_NAME,ENGINE,TABLE_COLLATION,ROW_FORMAT,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ($in) ORDER BY TABLE_NAME");
 $q->execute($tables);$found=[];foreach($q as $r){$found[$r['TABLE_NAME']]=true;echo 'TABLE '.json_encode($r,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";}
 echo 'table_metadata_count='.count($found).';expected_scope='.count($tables)."\n";
 $q=$pdo->prepare("SELECT TABLE_NAME,COLUMN_NAME,ORDINAL_POSITION,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,CHARACTER_SET_NAME,COLLATION_NAME,GENERATION_EXPRESSION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ($in) ORDER BY TABLE_NAME,ORDINAL_POSITION");
 $q->execute($tables);$n=0;foreach($q as $r){$n++;echo 'COLUMN '.json_encode($r,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";}echo "column_metadata_count={$n}\n";
 // MySQL and MariaDB expose different INFORMATION_SCHEMA.STATISTICS columns.
 // Detect optional fields instead of failing the entire audit on older/server-specific schemas.
 $statCols=$pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='information_schema' AND TABLE_NAME='STATISTICS'")->fetchAll(PDO::FETCH_COLUMN);
 $statSet=array_fill_keys(array_map('strtoupper',$statCols),true);
 $visibleExpr=isset($statSet['IS_VISIBLE'])?'IS_VISIBLE':'NULL AS IS_VISIBLE';
 $expressionExpr=isset($statSet['EXPRESSION'])?'EXPRESSION':'NULL AS EXPRESSION';
 echo 'index_is_visible_metadata_supported='.(isset($statSet['IS_VISIBLE'])?'true':'false')."\n";
 echo 'index_expression_metadata_supported='.(isset($statSet['EXPRESSION'])?'true':'false')."\n";
 $q=$pdo->prepare("SELECT TABLE_NAME,INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART,COLLATION,INDEX_TYPE,$visibleExpr,$expressionExpr FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ($in) ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX");
 $q->execute($tables);$n=0;foreach($q as $r){$n++;echo 'INDEX '.json_encode($r,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";}echo "index_metadata_count={$n}\n";
 $q=$pdo->prepare("SELECT TABLE_NAME,CONSTRAINT_NAME,COLUMN_NAME,ORDINAL_POSITION,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME,POSITION_IN_UNIQUE_CONSTRAINT FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME IN ($in) AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME,CONSTRAINT_NAME,ORDINAL_POSITION");
 $q->execute($tables);$n=0;foreach($q as $r){$n++;echo 'FK_COLUMN '.json_encode($r,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";}echo "foreign_key_column_count={$n}\n";
 $q=$pdo->prepare("SELECT tc.TABLE_NAME,tc.CONSTRAINT_NAME,rc.UPDATE_RULE,rc.DELETE_RULE,rc.MATCH_OPTION FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.REFERENTIAL_CONSTRAINTS rc ON rc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND rc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME AND rc.TABLE_NAME=tc.TABLE_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.CONSTRAINT_TYPE='FOREIGN KEY' AND tc.TABLE_NAME IN ($in) ORDER BY tc.TABLE_NAME,tc.CONSTRAINT_NAME");
 $q->execute($tables);$n=0;foreach($q as $r){$n++;echo 'FK_RULE '.json_encode($r,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";}echo "foreign_key_rule_count={$n}\n";
 $q=$pdo->prepare("SELECT TRIGGER_NAME,EVENT_MANIPULATION,EVENT_OBJECT_TABLE,ACTION_TIMING,ACTION_ORIENTATION,DEFINER,CHARACTER_SET_CLIENT,COLLATION_CONNECTION,SQL_MODE,SHA2(ACTION_STATEMENT,256) AS ACTION_SHA256 FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND EVENT_OBJECT_TABLE IN ($in) ORDER BY EVENT_OBJECT_TABLE,TRIGGER_NAME");
 $q->execute($tables);$n=0;foreach($q as $r){$n++;echo 'TRIGGER '.json_encode($r,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";}echo "trigger_metadata_count={$n}\n";
 $pdo->rollBack();$tx=false;
 echo "read_only_transaction=ROLLED_BACK\nproduction_database_changed=false\nproduction_application_files_changed=false\nproduction_migrations=NOT_RUN\nddl_dml_executed=NO\napplication_row_payloads_emitted=NO\nremote_temporary_files_created=NO\nphase_1c_test_1b_structural_audit=COMPLETED\n";
}catch(Throwable $e){if($tx&&$pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,"phase_1c_test_1b_structural_audit=ERROR\nerror_class=".get_class($e)."\nerror_message=".$e->getMessage()."\n");exit(20);}

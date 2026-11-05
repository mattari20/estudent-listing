<?php
declare(strict_types=1);
require getcwd().'/vendor/autoload.php';
$raw = base64_decode(getenv('EXPECTED_MIGRATIONS_B64') ?: '', true);
if ($raw === false || $raw === '') { fwrite(STDERR, "expected_manifest=INVALID\n"); exit(10); }
$expected = array_values(array_unique(array_filter(array_map('trim', explode("\n", $raw)))));
foreach ($expected as $name) if (!preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_[A-Za-z0-9_]+$/', $name)) { fwrite(STDERR, "expected_manifest=UNSAFE_ENTRY\n"); exit(10); }
sort($expected, SORT_STRING);
$cache = getcwd().'/bootstrap/cache/config.php';
if (is_file($cache)) {
    $app = require $cache;
    if (!is_array($app) || !is_array($app['database'] ?? null)) { fwrite(STDERR, "laravel_config_cache=INVALID\n"); exit(11); }
    $dbConfig = $app['database']; $configSource = 'laravel_config_cache';
} else {
    if (!class_exists(\Dotenv\Dotenv::class)) { fwrite(STDERR, "dotenv_library=UNAVAILABLE\n"); exit(11); }
    \Dotenv\Dotenv::createImmutable(getcwd())->safeLoad();
    $dbConfig = require getcwd().'/config/database.php'; $configSource = 'dotenv_plus_config_database';
}
$connectionName = (string)($dbConfig['default'] ?? 'mysql');
$conn = $dbConfig['connections'][$connectionName] ?? null;
if (!is_array($conn) || ($conn['driver'] ?? null) !== 'mysql') { fwrite(STDERR, "effective_database_driver=UNSUPPORTED_OR_MISSING\n"); exit(12); }
if (!empty($conn['url'])) {
    $parser = \Illuminate\Database\ConfigurationUrlParser::class;
    if (!class_exists($parser)) { fwrite(STDERR, "laravel_database_url_parser=UNAVAILABLE\n"); exit(12); }
    $conn = (new $parser())->parseConfiguration($conn);
}
$host=(string)($conn['host']??''); $db=(string)($conn['database']??''); $user=(string)($conn['username']??'');
$pass=(string)($conn['password']??''); $port=(string)($conn['port']??'3306'); $socket=(string)($conn['unix_socket']??'');
if ($db==='' || $user==='' || (!$socket && ($host==='' || !ctype_digit($port)))) { fwrite(STDERR, "effective_database_connection=INCOMPLETE\n"); exit(13); }
$deployed = array_map(static fn(string $p): string => basename($p,'.php'), glob(getcwd().'/database/migrations/*.php') ?: []);
sort($deployed,SORT_STRING);
$missingFiles=array_values(array_diff($expected,$deployed)); $extraFiles=array_values(array_diff($deployed,$expected));
$pdo=null; $open=false;
try {
    $dsn=$socket!=='' ? 'mysql:unix_socket='.$socket.';dbname='.$db.';charset=utf8mb4' : 'mysql:host='.$host.';port='.$port.';dbname='.$db.';charset=utf8mb4';
    $pdo=new PDO($dsn,$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>10]);
    $pdo->exec('SET TRANSACTION READ ONLY'); $pdo->beginTransaction(); $open=true;
    $identity=$pdo->query('SELECT DATABASE() AS d, @@hostname AS h, CURRENT_USER() AS u')->fetch();
    $fingerprint=hash('sha256',implode("\0",[strtolower((string)($identity['h']??'')),strtolower((string)($identity['d']??'')),strtolower((string)($identity['u']??''))]));
    $exists=$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='migrations'")->fetchColumn();
    if ((int)$exists!==1) throw new RuntimeException('Laravel migrations ledger table is missing.');
    $columns=$pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='migrations'")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('migration',$columns,true)) throw new RuntimeException('Laravel migrations ledger has no migration column.');
    $rows=$pdo->query('SELECT migration, COUNT(*) AS occurrences FROM migrations GROUP BY migration ORDER BY migration')->fetchAll();
    $ledger=[]; $duplicates=[];
    foreach($rows as $row){$n=(string)$row['migration'];$ledger[$n]=(int)$row['occurrences'];if((int)$row['occurrences']>1)$duplicates[]=$n;}
    $pending=array_values(array_diff($expected,array_keys($ledger)));
    $recorded=array_values(array_intersect($expected,array_keys($ledger)));
    $unknown=array_values(array_diff(array_keys($ledger),$expected));
    echo "phase_1c_full_migration_ledger_inventory=STARTED\n";
    echo "effective_database_config_source={$configSource}\n";
    echo "effective_database_connection_name={$connectionName}\n";
    echo "effective_database_fingerprint={$fingerprint}\n";
    echo "expected_source_migration_count=".count($expected)."\n";
    echo "deployed_source_migration_count=".count($deployed)."\n";
    echo "deployed_source_missing_expected_count=".count($missingFiles)."\n";
    echo "deployed_source_extra_count=".count($extraFiles)."\n";
    echo "ledger_distinct_migration_count=".count($rows)."\n";
    echo "expected_migrations_recorded_count=".count($recorded)."\n";
    echo "expected_migrations_pending_count=".count($pending)."\n";
    echo "duplicate_ledger_migration_count=".count($duplicates)."\n";
    echo "ledger_entries_not_in_expected_source_count=".count($unknown)."\n";
    echo "application_rows_read=0\n";
    foreach(['deployed_source_missing_expected'=>$missingFiles,'deployed_source_extra'=>$extraFiles,'expected_migrations_pending'=>$pending,'duplicate_ledger_migrations'=>$duplicates,'ledger_entries_not_in_expected_source'=>$unknown] as $label=>$values) foreach($values as $v) echo $label.'='.$v."\n";
    $status=($missingFiles||$extraFiles||$pending||$duplicates||$unknown)?'BLOCKED_RECONCILIATION_REQUIRED':'SOURCE_AND_LEDGER_INVENTORY_MATCH';
    echo "phase_1c_full_migration_ledger_classification={$status}\n";
    $pdo->rollBack(); $open=false;
    echo "read_only_transaction=ROLLED_BACK\nproduction_database_changed=false\nproduction_application_files_changed=false\nproduction_migrations=NOT_RUN\nddl_dml_executed=NO\npersistent_report_written=false\nremote_temporary_script_file_created=false\nphase_1c_full_migration_ledger_inventory=COMPLETED\n";
} catch(Throwable $e) {
    if($open && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR,"phase_1c_full_migration_ledger_inventory=ERROR\nerror_class=".get_class($e)."\nerror_message=".$e->getMessage()."\n"); exit(20);
}

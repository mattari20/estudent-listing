<?php
declare(strict_types=1);

require __DIR__.'/../../scripts/phase1c/schema-contract-reconciliation.php';

function expectState(array $result, string $state, string $label): void
{
    if (($result['state'] ?? null) !== $state) {
        throw new RuntimeException($label.': expected '.$state.', got '.(string)($result['state'] ?? 'unset'));
    }
}

$column = ['name'=>'status','definition_state'=>'COMPLETE','type'=>'varchar(32)','nullable'=>false,'default'=>'active','default_expression'=>null,'auto_increment'=>false,'generated'=>null];
expectState(Phase1D2BSchemaContractComparator::compareObject('column',$column,$column),'PASS','correct column');
expectState(Phase1D2BSchemaContractComparator::compareObject('column',$column,array_replace($column,['type'=>'int'])),'FAIL','wrong type');
expectState(Phase1D2BSchemaContractComparator::compareObject('column',$column,array_replace($column,['nullable'=>true])),'FAIL','wrong nullability');
expectState(Phase1D2BSchemaContractComparator::compareObject('column',$column,array_replace($column,['default'=>'inactive'])),'FAIL','wrong default');
expectState(Phase1D2BSchemaContractComparator::compareObject('column',$column,null,true),'MISSING','missing column with complete visibility');
expectState(Phase1D2BSchemaContractComparator::compareObject('column',$column,null,false),'UNKNOWN','incomplete metadata visibility');
expectState(Phase1D2BSchemaContractComparator::compareObject('column',array_replace($column,['definition_state'=>'NOT_CHECKED']),null,true),'NOT_CHECKED','incomplete source definition');

$index = ['name'=>'PRIMARY','definition_state'=>'COMPLETE','unique'=>true,'type'=>'BTREE','columns'=>[['name'=>'tenant_id','sequence'=>1,'prefix_length'=>null,'direction'=>'ASC'],['name'=>'record_id','sequence'=>2,'prefix_length'=>null,'direction'=>'ASC']]];
expectState(Phase1D2BSchemaContractComparator::compareObject('index',$index,$index),'PASS','correct composite primary key');
expectState(Phase1D2BSchemaContractComparator::compareObject('index',$index,array_replace($index,['columns'=>array_reverse($index['columns'])])),'FAIL','wrong composite index order');
$wrongPrefix=$index; $wrongPrefix['columns'][0]['prefix_length']=8;
expectState(Phase1D2BSchemaContractComparator::compareObject('index',$index,$wrongPrefix),'FAIL','wrong index prefix');
$wrongType=$index; $wrongType['type']='HASH';
expectState(Phase1D2BSchemaContractComparator::compareObject('index',$index,$wrongType),'FAIL','wrong index type');

$fk=['name'=>'orders_customer_fk','definition_state'=>'COMPLETE','source_table'=>'orders','source_columns'=>['customer_id'],'referenced_table'=>'customers','referenced_columns'=>['id'],'on_delete'=>'RESTRICT','on_update'=>'CASCADE'];
expectState(Phase1D2BSchemaContractComparator::compareObject('foreign_key',$fk,$fk),'PASS','correct foreign key');
$wrongFk=$fk; $wrongFk['referenced_columns']=['legacy_id'];
expectState(Phase1D2BSchemaContractComparator::compareObject('foreign_key',$fk,$wrongFk),'FAIL','wrong foreign key mapping');
$wrongAction=$fk; $wrongAction['on_delete']='CASCADE';
expectState(Phase1D2BSchemaContractComparator::compareObject('foreign_key',$fk,$wrongAction),'FAIL','wrong referential action');

$trigger=['name'=>'audit_guard','definition_state'=>'COMPLETE','table'=>'audit_events','timing'=>'BEFORE','event'=>'DELETE','orientation'=>'ROW','behavior_contract'=>'SIGNAL_45000_APPEND_ONLY','body_sha256'=>hash('sha256','SIGNAL 45000')];
expectState(Phase1D2BSchemaContractComparator::compareObject('trigger',$trigger,$trigger),'PASS','correct trigger contract');
expectState(Phase1D2BSchemaContractComparator::compareObject('trigger',$trigger,null,true),'MISSING','missing trigger');
$wrongTrigger=$trigger; $wrongTrigger['behavior_contract']='NOOP';
expectState(Phase1D2BSchemaContractComparator::compareObject('trigger',$trigger,$wrongTrigger),'FAIL','wrong trigger behavior');

$manifest=['tables'=>[
 'accounts'=>['definition_state'=>'NOT_CHECKED','table_options'=>['state'=>'NOT_CHECKED'],'columns'=>[],'indexes'=>[],'foreign_keys'=>[],'triggers'=>[]],
]];
$manifestResult=Phase1D2BSchemaContractComparator::reconcileManifest($manifest,[],['tables'=>true,'columns'=>true,'indexes'=>true,'foreign_keys'=>true,'triggers'=>true]);
expectState(['state'=>$manifestResult['state']],'NOT_CHECKED','manifest never claims incomplete definitions PASS');

$source=['001_create_accounts','002_add_account_key','003_add_guard'];
$objects=[
 '001_create_accounts'=>['PASS','PASS'],
 '002_add_account_key'=>['PASS'],
 '003_add_guard'=>['NOT_CHECKED'],
];
$rows=[
 ['migration'=>'001_create_accounts','batch'=>1,'id'=>1],
 ['migration'=>'001_create_accounts','batch'=>2,'id'=>2],
 ['migration'=>'004_unexpected','batch'=>0,'id'=>3],
 ['migration'=>'002_add_account_key','batch'=>3,'id'=>4],
];
$ledger=Phase1D2BSchemaContractComparator::reconcileMigrationLedger($source,$rows,$objects,true,true);
expectState(['state'=>$ledger['state']],'FAIL','ledger anomalies fail');
if (count($ledger['duplicate_ledger_entries'])!==1) throw new RuntimeException('duplicate ledger row not detected');
if (count($ledger['ledger_entries_without_source_files'])!==1) throw new RuntimeException('unexpected ledger entry not detected');
if (count($ledger['invalid_ledger_rows'])!==1) throw new RuntimeException('invalid batch not detected');
if ($ledger['migration_object_reconciliation'][2]['consistency_state']!=='NOT_CHECKED') throw new RuntimeException('unverified object mapping must remain NOT_CHECKED');
if ($ledger['migration_object_reconciliation'][1]['consistency_state']!=='PASS') throw new RuntimeException('valid ledger/object pair should pass');
$missingLedger=Phase1D2BSchemaContractComparator::reconcileMigrationLedger($source,[['migration'=>'001_create_accounts','batch'=>1]],['001_create_accounts'=>['PASS'],'002_add_account_key'=>['PASS']],true,false);
if (!in_array('002_add_account_key',$missingLedger['source_files_not_recorded'],true)) throw new RuntimeException('missing ledger entry not detected');
if ($missingLedger['migration_object_reconciliation'][1]['consistency_state']!=='FAIL') throw new RuntimeException('objects present without ledger must fail');
$orderCheck=Phase1D2BSchemaContractComparator::reconcileMigrationLedger(
 ['001_first','002_second'],
 [
  ['migration'=>'002_second','batch'=>1,'id'=>1],
  ['migration'=>'001_first','batch'=>1,'id'=>2],
 ],
 ['001_first'=>['PASS'],'002_second'=>['PASS']],
 true,
 true
);
expectState(['state'=>$orderCheck['ordering_state']],'FAIL','ledger ordering anomaly');
if ($orderCheck['ordering_anomalies']===[]) throw new RuntimeException('ordering anomaly details missing');
$gapCheck=Phase1D2BSchemaContractComparator::reconcileMigrationLedger(
 ['001_first','003_third'],
 [['migration'=>'001_first','batch'=>1,'id'=>1],['migration'=>'003_third','batch'=>3,'id'=>2]],
 ['001_first'=>['PASS'],'003_third'=>['PASS']],
 true,
 true
);
if ($gapCheck['batch_gaps']!==[[2,2]] || $gapCheck['batch_gap_state']!=='NOT_CHECKED') throw new RuntimeException('batch gap must be recorded as informational NOT_CHECKED');
$partial=Phase1D2BSchemaContractComparator::reconcileMigrationLedger(
 ['001_partial'],
 [['migration'=>'001_partial','batch'=>1,'id'=>1]],
 ['001_partial'=>['PASS','MISSING']],
 true,
 true
);
if ($partial['migration_object_reconciliation'][0]['consistency_state']!=='FAIL') throw new RuntimeException('partially applied migration objects must fail');
$unknown=Phase1D2BSchemaContractComparator::reconcileMigrationLedger($source,[],[],false,false);
expectState(['state'=>$unknown['state']],'UNKNOWN','ledger visibility failure');
if ($unknown['migration_object_reconciliation'][0]['ledger_state']!=='UNKNOWN') throw new RuntimeException('ledger permission failure must be UNKNOWN');

$a=Phase1D2BSchemaContractComparator::sanitizeDeterministically(['z'=>1,'a'=>['y'=>2,'b'=>1]]);
$b=Phase1D2BSchemaContractComparator::sanitizeDeterministically(['a'=>['b'=>1,'y'=>2],'z'=>1]);
if (json_encode($a)!==json_encode($b)) throw new RuntimeException('evidence ordering is not deterministic');

echo "phase1d2b_comparator=PASS positive_and_negative_fixtures=yes metadata_fail_closed=yes ledger_partial_application=yes deterministic=yes\n";

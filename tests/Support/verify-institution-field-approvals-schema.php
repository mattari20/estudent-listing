<?php
declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
function bad(string $m): never { fwrite(STDERR, "FAILED: {$m}\n"); exit(1); }
$t='institution_field_approvals';
if (!Schema::hasTable($t)) bad('table missing');
$expected=[
'id'=>['bigint',false],'institution_id'=>['bigint',false],'field_name'=>['varchar',false],
'expected_current_value'=>['longtext',true],'proposed_value'=>['longtext',false],
'source_id'=>['bigint',false],'source_change_observation_id'=>['bigint',false],
'source_snapshot_id'=>['bigint',false],'evidence_id'=>['bigint',false],
'evidence_url'=>['text',false],'evidence_excerpt'=>['longtext',false],
'content_fingerprint'=>['varchar',false],'source_observed_at'=>['varchar',false],
'evidence_observed_at'=>['varchar',true],'proposal_hash'=>['char',false],
'status'=>['varchar',false],'created_by'=>['bigint',false],'reviewer_id'=>['bigint',true],
'decision_at'=>['timestamp',true],'decision_reason'=>['text',true],'applied_at'=>['timestamp',true],
'application_outcome'=>['varchar',true],'applied_refresh_event_id'=>['bigint',true],
'created_at'=>['timestamp',true],'updated_at'=>['timestamp',true]];
$cols=collect(Schema::getColumns($t))->keyBy('name');
if($cols->count()!==26) bad('expected 26 columns, found '.$cols->count());
foreach($expected as $n=>[$type,$null]){
 if(!$cols->has($n)) bad("missing column {$n}");
 $c=$cols->get($n); $actual=strtolower((string)($c['type_name']??$c['type']??''));
 if(!str_contains($actual,$type)) bad("wrong type for {$n}: {$actual}");
 if(($c['nullable']??null)!==$null) bad("wrong nullability for {$n}");
}
if(($cols->get('status')['default']??null)!=='pending') bad('status default not pending');
if(!($cols->get('id')['auto_increment']??false)) bad('id is not auto increment');
$idx=collect(Schema::getIndexes($t))->keyBy('name');
$expectedIdx=[
'PRIMARY'=>[['id'],true,true],
'institution_field_approvals_proposal_hash_unique'=>[['proposal_hash'],true,false],
'inst_field_approval_state_idx'=>[['institution_id','field_name','status'],false,false],
'inst_field_approval_source_idx'=>[['source_id','source_change_observation_id'],false,false],
'inst_field_approval_decision_idx'=>[['status','decision_at'],false,false]];
foreach($expectedIdx as $n=>[$c,$u,$p]){
 $i=$idx->get($n); if(!is_array($i)) bad("missing index {$n}");
 if(array_values($i['columns']??[])!==$c||(bool)($i['unique']??false)!==$u||(bool)($i['primary']??false)!==$p) bad("index mismatch {$n}");
}
$fks=collect(Schema::getForeignKeys($t));
$expectedFk=['institution_id'=>['institutions','id'],'source_id'=>['sources','id'],
'source_change_observation_id'=>['source_change_observations','id'],
'source_snapshot_id'=>['source_snapshots','id'],'evidence_id'=>['evidence','id'],
'created_by'=>['users','id'],'reviewer_id'=>['users','id'],
'applied_refresh_event_id'=>['official_source_refresh_events','id']];
foreach($expectedFk as $c=>[$ft,$fc]){
 $m=$fks->filter(fn(array $f):bool=>array_values($f['columns']??[])===[$c]&&($f['foreign_table']??null)===$ft&&array_values($f['foreign_columns']??[])===[$fc]);
 if($m->count()!==1) bad("missing/duplicate FK {$c} -> {$ft}.{$fc}");
 if(!in_array(strtolower((string)($m->first()['on_delete']??'')),['restrict','no action'],true)) bad("non-restrictive delete for {$c}");
}
if($fks->count()!==8) bad('unexpected FK count');
$m='2026_10_29_000001_create_institution_field_approvals_table';
if(DB::table('migrations')->where('migration',$m)->count()!==1) bad('migration ledger count is not one');
echo "Verified 26 columns, defaults, five expected indexes, eight restrictive foreign keys, and one ledger entry.\n";

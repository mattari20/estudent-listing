<?php
declare(strict_types=1);

/**
 * Standalone, allowlisted, read-only Phase 1C diagnostic.
 * No Laravel migration classes are loaded or invoked.
 */
final class Phase1CReadOnlyDiagnostic
{
    private const TABLES = [
        'assertions','change_events','data_reverification_queue','data_reverification_queue_identities',
        'enrichment_import_rows','evidence','governed_execution_leases','governed_execution_run_events',
        'governed_execution_runs','identity_match_candidates','identity_resolution_decisions',
        'import_batches','import_conflict_events','import_conflicts','import_promotion_decisions',
        'import_promotion_events','import_reconciliation_events','import_rows','institute_discovery_run_outcomes',
        'institutions','migrations','monitoring_alert_identities','monitoring_alerts','monitoring_policy_run_sources',
        'monitoring_policy_runs','official_source_refresh_events','password_reset_tokens','provenance_review_events',
        'reverification_events','security_audit_events','source_change_observations','source_monitoring_policies',
        'source_retrieval_runs','source_snapshot_reconciliation_requests','source_snapshots','sources',
        'structured_enrichment_promotion_events','users','verification_decisions'
    ];

    private const INDEXES = [
        ['governed_execution_leases','governed_execution_leases_task_key_unique',['task_key'],true],
        ['governed_execution_leases','governed_execution_leases_expires_at_index',['expires_at'],false],
        ['source_retrieval_runs','source_retrieval_runs_idempotency_key_unique',['idempotency_key'],true],
        ['source_snapshots','source_snapshots_capture_key_unique',['capture_key'],true],
        ['source_change_observations','source_observations_current_snapshot_unique',['current_snapshot_id'],true],
        ['institute_discovery_run_outcomes','institute_discovery_run_outcomes_idempotency_key_unique',['idempotency_key'],true],
        ['change_events','change_events_source_link_unique',['source_change_assertion_link_id'],true],
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
        ['data_reverification_queue_identities','data_reverification_queue_identities_active_queue_id_index',['active_queue_id'],false],
        ['monitoring_alert_identities','monitoring_alert_identities_active_alert_id_index',['active_alert_id'],false],
        ['import_conflict_events','import_conflict_events_import_conflict_id_occurred_at_index',['import_conflict_id','occurred_at'],false],
        ['monitoring_policy_run_sources','monitoring_policy_source_status_idx',['retrieval_run_id'],false],
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

    public function __construct(PDO $pdo) { $this->pdo=$pdo; }

    public function collect(): array
    {
        $this->pdo->exec('SET TRANSACTION READ ONLY');
        $this->pdo->beginTransaction();
        try {
            $version=(string)$this->pdo->query('SELECT VERSION()')->fetchColumn();
            $this->inventory();
            $result=[
                'schema_version'=>'phase1c-readonly-diagnostic/v1',
                'database'=>['engine'=>stripos($version,'mariadb')!==false?'mariadb':'mysql','server_version'=>$version],
                'metadata_visibility'=>['complete'=>$this->absenceProof],
                'tables'=>$this->tableMetadata(),
                'columns'=>$this->columnMetadata(),
                'indexes'=>$this->indexMetadata(),
                'expected_index_reconciliation'=>$this->indexReconciliation(),
                'foreign_keys'=>$this->foreignKeys(),
                'queue_mapping'=>$this->queueMapping(),
                'alert_mapping'=>$this->alertMapping(),
                'orphan_checks'=>$this->orphanChecks(),
                'duplicate_checks'=>$this->duplicateChecks(),
                'registry_counts'=>['tables'=>count(self::TABLES),'expected_indexes'=>count(self::INDEXES),'orphan_checks'=>count(self::ORPHANS),'duplicate_checks'=>count(self::DUPLICATES)],
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

    private function inventory(): void
    {
        $placeholders=implode(',',array_fill(0,count(self::TABLES),'?'));
        $inventory=$this->pdo->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE' AND TABLE_NAME IN ($placeholders)");
        $inventory->execute(self::TABLES);
        foreach($inventory->fetchAll(PDO::FETCH_COLUMN) as $t) $this->tables[(string)$t]=true;
        $this->absenceProof=$this->hasDatabaseWideSelect();
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
            if(!isset($this->tables[$t])) {$out[]=['table'=>$t,'state'=>$this->absenceProof?'MISSING':'UNKNOWN','reason'=>$this->absenceProof?'TABLE_ABSENT':'METADATA_VISIBILITY_NOT_PROVEN'];continue;}
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
                $q=$this->pdo->prepare('SELECT COLUMN_NAME,ORDINAL_POSITION,COLUMN_DEFAULT,IS_NULLABLE,DATA_TYPE,COLUMN_TYPE,CHARACTER_MAXIMUM_LENGTH,CHARACTER_OCTET_LENGTH,NUMERIC_PRECISION,NUMERIC_SCALE,DATETIME_PRECISION,CHARACTER_SET_NAME,COLLATION_NAME,COLUMN_KEY,EXTRA,PRIVILEGES,COLUMN_COMMENT,GENERATION_EXPRESSION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');
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
            if(!isset($this->tables[$table])) {$out[]=$expected+['state'=>$this->absenceProof?'NOT_CHECKED':'UNKNOWN','reason'=>$this->absenceProof?'TABLE_MISSING':'METADATA_VISIBILITY_NOT_PROVEN'];continue;}
            if(isset($this->indexErrors[$table])) {$out[]=$expected+['state'=>'UNKNOWN','reason'=>'INDEX_METADATA_UNAVAILABLE','error'=>$this->indexErrors[$table]];continue;}
            if(isset($this->columnErrors[$table])) {$out[]=$expected+['state'=>'UNKNOWN','reason'=>'COLUMN_METADATA_UNAVAILABLE','error'=>$this->columnErrors[$table]];continue;}
            $rows=$actual[$table][$name]??[];
            if(!$rows) {$out[]=$expected+['state'=>'MISSING'];continue;}
            usort($rows,fn($a,$b)=>(int)$a['SEQ_IN_INDEX']<=>(int)$b['SEQ_IN_INDEX']);
            $cols=array_map(fn($r)=>(string)$r['COLUMN_NAME'],$rows);
            $isUnique=(int)$rows[0]['NON_UNIQUE']===0;
            $type=strtoupper((string)$rows[0]['INDEX_TYPE']);
            $ok=$cols===$columns && $isUnique===$unique && $type==='BTREE' && count(array_filter($rows,fn($r)=>$r['SUB_PART']!==null))===0;
            $out[]=$expected+['state'=>$ok?'PASS':'FAIL','observed'=>['columns'=>$cols,'unique'=>$isUnique,'type'=>$type,'prefix_lengths'=>array_column($rows,'SUB_PART')]];
        }
        return $out;
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
        $ready=$this->has('data_reverification_queue_identities',['identity_key','subject_type','subject_id','attribute','active_queue_id']);
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
        $ready=$this->has('monitoring_alert_identities',['dedupe_key','active_alert_id']);
        $records=[];
        foreach(array_slice($rows,0,2) as $i=>$r) {
            if(!$ready) {$records[]=['record_ordinal'=>$i+1,'state'=>isset($this->columnErrors[$target])?'UNKNOWN':'NOT_CHECKED'];continue;}
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

    private function hasDatabaseWideSelect(): bool
    {
        try {
            $database=(string)$this->pdo->query('SELECT DATABASE()')->fetchColumn();
            $grants=$this->pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN);
            foreach($grants as $grant) {
                $grant=(string)$grant;
                if(preg_match('/^GRANT\\s+ALL PRIVILEGES\\s+ON\\s+(?:\\*|'.preg_quote(chr(96).$database.chr(96),'/').')\\.\\*/i',$grant)) return true;
                if(preg_match('/^GRANT\\s+[^;]*\\bSELECT\\b[^;]*\\s+ON\\s+(?:\\*|'.preg_quote(chr(96).$database.chr(96),'/').')\\.\\*/i',$grant)) return true;
            }
        } catch(Throwable $e) {
            return false;
        }
        return false;
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
        if ($value !== null && preg_match('/password|secret|token|credential|private.?key|api.?key/i', $column)) return '[REDACTED]';
        return $value;
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
    $cache=$root.'/bootstrap/cache/config.php';
    if(is_file($cache)) {$config=require $cache;$db=$config['database']??[];}
    else {
        if(class_exists(Dotenv\Dotenv::class)) Dotenv\Dotenv::createImmutable($root)->safeLoad();
        $db=require $root.'/config/database.php';
    }
    $name=(string)($db['default']??'mysql'); $c=$db['connections'][$name]??null;
    if(!is_array($c)||($c['driver']??null)!=='mysql') throw new RuntimeException('Unsupported configured database driver.');
    if(!empty($c['url'])) {$parser=Illuminate\Database\ConfigurationUrlParser::class;$c=(new $parser())->parseConfiguration($c);}
    $database=(string)($c['database']??'');$charset=(string)($c['charset']??'utf8mb4');
    $dsn=!empty($c['unix_socket'])?'mysql:unix_socket='.$c['unix_socket'].';dbname='.$database.';charset='.$charset:'mysql:host='.$c['host'].';port='.($c['port']??3306).';dbname='.$database.';charset='.$charset;
    return new PDO($dsn,(string)$c['username'],(string)$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>10,PDO::ATTR_EMULATE_PREPARES=>false]);
}

if(PHP_SAPI==='cli' && realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__) {
    try {
        $root=$argv[1]??'';
        if($root===''||!str_starts_with($root,'/')) throw new RuntimeException('Invalid project root.');
        $e=(new Phase1CReadOnlyDiagnostic(phase1cDiagnosticPdo($root)))->collect();
        echo json_encode($e,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL;
    } catch(Throwable $e) {
        $i=$e instanceof PDOException&&is_array($e->errorInfo??null)?$e->errorInfo:[];
        echo json_encode(['diagnostic_state'=>'FAILED_SAFE','exception_class'=>(new ReflectionClass($e))->getShortName(),'sqlstate'=>(string)($i[0]??($e->getCode()?:'unavailable')),'vendor_code'=>(string)($i[1]??'unavailable')],JSON_THROW_ON_ERROR).PHP_EOL;
        exit(1);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** Read-only reconciliation of historical promotion events by stable import-row ID. */
class ReconcileImportPromotionFailures extends Command
{
    protected $signature = 'institutes:reconcile-promotion-failures {--batch-id= : Optional import batch ID} {--json : Print machine-readable JSON}';
    protected $description = 'Group historical promotion-failure events by stable import row without writes.';

    public function handle(): int
    {
        $report = ['check'=>'import_promotion_failure_reconciliation','checked_at'=>now()->toIso8601String(),'mode'=>'read_only','mutation_performed'=>false,'database_mutations'=>0,'production_write_authorization'=>false,'summary'=>[],'rows'=>[],'unresolved_events'=>[],'errors'=>[]];
        try {
            foreach (['import_promotion_events','import_rows'] as $table) if (!Schema::hasTable($table)) $report['errors'][]='required_table_missing:'.$table;
            if ($report['errors']) return $this->finish($report,self::FAILURE);
            $rawBatch=$this->option('batch-id');
            $batchId=filter_var($rawBatch,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            if ($rawBatch!==null && $batchId===false) { $report['errors'][]='invalid_batch_id'; return $this->finish($report,self::FAILURE); }
            $query=DB::table('import_promotion_events as e')->leftJoin('import_rows as r','r.id','=','e.import_row_id')
                ->select(['e.id as event_id','e.import_row_id','e.import_batch_id','e.event_type','e.reason','e.metadata','e.occurred_at','r.id as current_row_id','r.status as current_row_status','r.match_state as current_match_state','r.canonical_subject_type as current_canonical_subject_type','r.canonical_subject_id as current_canonical_subject_id','r.validation_errors as current_validation_errors'])
                ->orderBy('e.import_row_id')->orderBy('e.occurred_at')->orderBy('e.id');
            if ($batchId!==false && $batchId!==null) $query->where('e.import_batch_id',$batchId);
            $events=$query->get(); $grouped=[]; $unresolved=[];
            foreach ($events as $e) {
                if ($e->import_row_id===null || $e->current_row_id===null) {
                    $unresolved[]=['event_id'=>(int)$e->event_id,'import_row_id'=>$e->import_row_id===null?null:(int)$e->import_row_id,'batch_id'=>$e->import_batch_id===null?null:(int)$e->import_batch_id,'event_type'=>$e->event_type,'occurred_at'=>$e->occurred_at,'resolution'=>'needs-manual-reconciliation']; continue;
                }
                $id=(int)$e->import_row_id; $category=$this->classify((string)$e->event_type,(string)($e->reason??''),$e->metadata);
                if (!isset($grouped[$id])) $grouped[$id]=['import_row_id'=>$id,'batch_ids'=>[],'event_ids'=>[],'event_categories'=>[],'latest_event_at'=>null,'latest_failure_category'=>null,'current_row_status'=>$e->current_row_status,'current_match_state'=>$e->current_match_state,'current_canonical_subject_type'=>$e->current_canonical_subject_type,'current_canonical_subject_id'=>$e->current_canonical_subject_id===null?null:(int)$e->current_canonical_subject_id,'current_validation_errors'=>$this->decodeJson($e->current_validation_errors),'current_state_requires_review'=>$this->requiresReview($e->current_row_status,$e->current_match_state)];
                $grouped[$id]['batch_ids'][]=(int)$e->import_batch_id; $grouped[$id]['event_ids'][]=(int)$e->event_id; $grouped[$id]['event_categories'][]=$category; $grouped[$id]['latest_event_at']=$e->occurred_at; $grouped[$id]['latest_failure_category']=$category;
            }
            foreach ($grouped as &$row) { $row['batch_ids']=array_values(array_unique($row['batch_ids'])); $row['event_categories']=array_values(array_unique($row['event_categories'])); $row['historical_event_count']=count($row['event_ids']); $row['historical_failure_does_not_prove_current_unresolved_state']=true; $row['proposed_disposition']=$row['current_state_requires_review']?'review_current_row_and_evidence':'verify_current_state_before_closing_historical_event'; } unset($row);
            $report['rows']=array_values($grouped); $report['unresolved_events']=$unresolved;
            $report['summary']=['source_event_count'=>$events->count(),'unique_stable_row_count'=>count($grouped),'unresolved_event_count'=>count($unresolved),'duplicate_or_identity_collision_event_count'=>$events->filter(fn($e)=>$this->classify((string)$e->event_type,(string)($e->reason??''),$e->metadata)==='duplicate_or_identity_collision')->count(),'validation_or_missing_field_event_count'=>$events->filter(fn($e)=>$this->classify((string)$e->event_type,(string)($e->reason??''),$e->metadata)==='validation_or_missing_field')->count(),'rows_currently_flagged_for_review'=>collect($report['rows'])->where('current_state_requires_review',true)->count(),'baseline_76_event_total_is_not_assumed'=>true];
            $report['limitations']=['Categories are inferred from stored event type, reason and metadata; unknown categories require review.','Current row status is not source-evidence verification.','No event history, import row, or canonical record is changed.'];
            return $this->finish($report,self::SUCCESS);
        } catch (Throwable $e) { $report['errors'][]='reconciliation_failed:'.class_basename($e); return $this->finish($report,self::FAILURE); }
    }
    private function classify(string $type,string $reason,mixed $metadata): string {
        $text=mb_strtolower($type.' '.$reason.' '.(is_string($metadata)?$metadata:json_encode($metadata)));
        if (preg_match('/duplicate|collision|identity|already exists|matched to another/',$text)) return 'duplicate_or_identity_collision';
        if (preg_match('/validation|missing|required|invalid field|field is empty/',$text)) return 'validation_or_missing_field';
        return 'other_or_unclassified';
    }
    private function requiresReview(?string $status,?string $match): bool {
        return in_array(mb_strtolower((string)$status),['failed','rejected','needs_review','pending_review'],true)||in_array(mb_strtolower((string)$match),['unresolved','ambiguous','conflict'],true);
    }
    private function decodeJson(mixed $value): mixed {
        if (!is_string($value)||trim($value)==='') return $value;
        $decoded=json_decode($value,true); return json_last_error()===JSON_ERROR_NONE?$decoded:$value;
    }
    private function finish(array $report,int $code): int {
        $report['database_mutations']=0; $report['mutation_performed']=false;
        if ($this->option('json')) $this->line(json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        else { $this->table(['Metric','Value'],collect($report['summary']??[])->map(fn($v,$k)=>[$k,$v])->values()->all()); $this->info('mode=read_only'); $this->line('database_mutations=0'); }
        return $code;
    }
}
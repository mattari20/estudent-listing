<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ValidateGcuCandidateDisposition extends Command
{
    protected $signature = 'institutes:gcu-disposition-validate {file : Reviewed disposition CSV} {--expected-rows=112 : Expected rows from the exact source batch} {--json : Print machine-readable JSON}';
    protected $description = 'Validate a GCU candidate disposition artifact before human review; never changes candidate data.';

    private const REQUIRED = ['source_batch_id','import_row_id','row_number','candidate_name','source_url','snapshot_id','extractor_version','disposition','reviewer_rationale'];
    private const DISPOSITIONS = ['keep-for-review','duplicate-of-row','invalid-navigation-or-footer','unsupported-by-source','needs-official-evidence','already-dispositioned'];

    public function handle(): int
    {
        $report=['check'=>'gcu_candidate_disposition_artifact','mode'=>'read_only','mutation_performed'=>false,'database_mutations'=>0,'expected_rows'=>max(0,(int)$this->option('expected-rows')),'actual_rows'=>0,'errors'=>[],'summary'=>[]];
        $path=(string)$this->argument('file');
        if (!is_file($path)||!is_readable($path)) $report['errors'][]='file_missing_or_unreadable';
        else {
            $handle=fopen($path,'rb');
            if ($handle===false) $report['errors'][]='file_unreadable';
            else {
                $header=fgetcsv($handle);
                if (!is_array($header)||array_diff(self::REQUIRED,$header)!==[]) $report['errors'][]='required_columns_missing';
                else {
                    $idx=array_flip($header); $ids=[]; $duplicateTargets=[]; $blankRationale=0; $invalidDispositions=0; $blankIds=0; $rows=[];
                    while (($values=fgetcsv($handle))!==false) {
                        if ($values===[null]||$values===[]) continue;
                        $row=[]; foreach ($header as $i=>$key) $row[$key]=trim((string)($values[$i]??''));
                        $rows[]=$row; $id=$row['import_row_id'];
                        if ($id==='') $blankIds++; elseif(isset($ids[$id])) $report['errors'][]='duplicate_import_row_id:'.$id; else $ids[$id]=true;
                        if (trim($row['reviewer_rationale'])==='') $blankRationale++;
                        if (!in_array($row['disposition'],self::DISPOSITIONS,true)) $invalidDispositions++;
                        if ($row['disposition']==='duplicate-of-row') $duplicateTargets[]=['source'=>$id,'target'=>$row['duplicate_target_import_row_id']??''];
                    }
                    fclose($handle); $report['actual_rows']=count($rows);
                    if ($report['actual_rows']!==$report['expected_rows']) $report['errors'][]='row_count_mismatch';
                    if ($blankIds>0) $report['errors'][]='blank_import_row_id';
                    if ($blankRationale>0) $report['errors'][]='blank_reviewer_rationale';
                    if ($invalidDispositions>0) $report['errors'][]='invalid_disposition';
                    foreach ($duplicateTargets as $target) {
                        if ($target['target']===''||$target['target']===$target['source']||!isset($ids[$target['target']])) $report['errors'][]='invalid_duplicate_target_for_row:'.$target['source'];
                    }
                    $report['summary']=['unique_import_row_ids'=>count($ids),'blank_import_row_ids'=>$blankIds,'blank_rationales'=>$blankRationale,'invalid_dispositions'=>$invalidDispositions,'duplicate_disposition_rows'=>count($duplicateTargets),'duplicate_target_checks'=>count($duplicateTargets)];
                }
            }
        }
        $report['status']=$report['errors']===[]?'VALIDATION_PASSED_REVIEW_REQUIRED':'BLOCKED';
        if ($this->option('json')) $this->line(json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        else { $this->table(['Metric','Value'],collect($report['summary'])->map(fn($v,$k)=>[$k,$v])->values()->all()); $this->info('status='.$report['status']); $this->line('database_mutations=0'); }
        return $report['errors']===[]?self::SUCCESS:self::FAILURE;
    }
}
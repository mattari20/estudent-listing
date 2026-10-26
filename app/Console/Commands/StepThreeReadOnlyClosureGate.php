<?php

namespace App\\Console\\Commands;

use Illuminate\\Console\\Command;

class StepThreeReadOnlyClosureGate extends Command
{
    protected $signature='institutes:step3-closure-gate {report : Fresh offering-gap JSON report} {--expected-rows=36 : Exact expected row count} {--max-age-hours=24 : Maximum accepted report age} {--json : Print machine-readable JSON}';
    protected $description='Evaluate whether a read-only Step 3 report is eligible for human closure review; never closes Step 3 or changes data.';

    public function handle(): int
    {
        $r=['check'=>'step3_read_only_closure_gate','mode'=>'read_only','mutation_performed'=>false,'database_mutations'=>0,'production_write_authorization'=>false,'status'=>'BLOCKED','errors'=>[],'summary'=>[]];
        $path=(string)$this->argument('report');$expected=max(0,(int)$this->option('expected-rows'));$maxAge=max(1,(int)$this->option('max-age-hours'));
        if(!is_file($path)||!is_readable($path))$r['errors'][]='report_missing_or_unreadable';
        else{
            $report=json_decode((string)file_get_contents($path),true);
            if(!is_array($report))$r['errors'][]='report_invalid_json';
            else{
                foreach(['records','checked_at','mutation_performed','database_mutations','production_write_authorization'] as $key)if(!array_key_exists($key,$report))$r['errors'][]='required_report_field_missing:'.$key;
                if(($report['mutation_performed']??null)!==false)$r['errors'][]='report_mutation_marker_not_false';
                if(($report['database_mutations']??null)!==0)$r['errors'][]='report_database_mutations_not_zero';
                if(($report['production_write_authorization']??null)!==false)$r['errors'][]='report_write_authorization_not_false';
                if(!is_array($report['records']??null))$r['errors'][]='report_records_not_array';
                $records=is_array($report['records']??null)?$report['records']:[];$count=count($records);
                if($count!==$expected)$r['errors'][]='report_record_count_mismatch';
                $timestamp=is_string($report['checked_at']??null)?strtotime($report['checked_at']):false;$age=null;
                if($timestamp===false)$r['errors'][]='invalid_report_timestamp';
                else{$age=max(0,(time()-$timestamp)/3600);if($age>$maxAge)$r['errors'][]='report_stale';}
                $reviewed=0;$missingRationale=0;$unreviewed=[];
                foreach($records as $i=>$record){
                    if(!is_array($record)){$r['errors'][]='invalid_record_at_index:'.$i;continue;}
                    $status=mb_strtolower(trim((string)($record['review_status']??'')));
                    $rationale=trim((string)($record['reviewer_rationale']??''));
                    if($status!=='reviewed')$unreviewed[]=$i;else $reviewed++;
                    if($rationale==='')$missingRationale++;
                    if(($record['canonical_mutation_performed']??null)!==false)$r['errors'][]='record_mutation_marker_not_false_at_index:'.$i;
                }
                if($unreviewed)$r['errors'][]='records_not_reviewed';
                if($missingRationale>0)$r['errors'][]='reviewer_rationale_missing';
                $r['summary']=['record_count'=>$count,'expected_record_count'=>$expected,'reviewed_record_count'=>$reviewed,'unreviewed_record_count'=>count($unreviewed),'records_missing_rationale'=>$missingRationale,'report_age_hours'=>$age,'max_age_hours'=>$maxAge];
            }
        }
        $r['status']=$r['errors']===[]?'ELIGIBLE_FOR_HUMAN_CLOSURE_REVIEW':'BLOCKED';
        $r['closure_performed']=false;$r['note']='Eligibility is not closure approval. Human sign-off and source-evidence review remain required.';
        if($this->option('json'))$this->line(json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        else{$this->table(['Metric','Value'],collect($r['summary'])->map(fn($v,$k)=>[$k,$v])->values()->all());$this->info('status='.$r['status']);$this->line('closure_performed=false');$this->line('database_mutations=0');}
        return $r['errors']===[]?self::SUCCESS:self::FAILURE;
    }
}
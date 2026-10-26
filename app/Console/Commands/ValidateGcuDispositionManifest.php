<?php

namespace App\\Console\\Commands;

use Illuminate\\Console\\Command;

class ValidateGcuDispositionManifest extends Command
{
    protected $signature='institutes:gcu-manifest-validate {file : Disposition CSV} {manifest : JSON manifest} {--expected-rows=112 : Expected candidates in this exact batch} {--json : Print machine-readable JSON}';
    protected $description='Verify a GCU disposition CSV against a checksum-bearing manifest without changing data.';

    public function handle(): int
    {
        $r=['check'=>'gcu_disposition_manifest','mode'=>'read_only','mutation_performed'=>false,'database_mutations'=>0,'status'=>'BLOCKED','errors'=>[],'summary'=>[]];
        $file=(string)$this->argument('file'); $manifest=(string)$this->argument('manifest');
        if(!is_file($file)||!is_readable($file)) $r['errors'][]='disposition_file_missing_or_unreadable';
        if(!is_file($manifest)||!is_readable($manifest)) $r['errors'][]='manifest_missing_or_unreadable';
        if(!$r['errors']){
            $m=json_decode((string)file_get_contents($manifest),true);
            if(!is_array($m)) $r['errors'][]='manifest_invalid_json';
            else {
                foreach(['batch_id','source_file_sha256','candidate_count','generated_at'] as $k) if(!array_key_exists($k,$m)) $r['errors'][]='manifest_field_missing:'.$k;
                $hash=hash_file('sha256',$file); $expected=max(0,(int)$this->option('expected-rows'));
                $lines=0; $h=fopen($file,'rb');
                if($h!==false){fgetcsv($h);while(($row=fgetcsv($h))!==false)if($row!==[null]&&$row!==[])$lines++;fclose($h);}
                if(isset($m['source_file_sha256'])&&!hash_equals((string)$m['source_file_sha256'],$hash))$r['errors'][]='checksum_mismatch';
                if(isset($m['candidate_count'])&&(int)$m['candidate_count']!==$lines)$r['errors'][]='manifest_candidate_count_mismatch';
                if($lines!==$expected)$r['errors'][]='expected_candidate_count_mismatch';
                if(trim((string)($m['batch_id']??''))==='')$r['errors'][]='blank_batch_id';
                if(!is_string($m['generated_at']??null)||strtotime($m['generated_at'])===false)$r['errors'][]='invalid_generated_at';
                $r['summary']=['batch_id'=>$m['batch_id']??null,'csv_sha256'=>$hash,'manifest_sha256'=>$m['source_file_sha256']??null,'candidate_count'=>$lines,'manifest_candidate_count'=>$m['candidate_count']??null,'expected_candidate_count'=>$expected,'checksum_matches'=>isset($m['source_file_sha256'])&&hash_equals((string)$m['source_file_sha256'],$hash)];
            }
        }
        $r['status']=$r['errors']===[]?'MANIFEST_VALID_REVIEW_REQUIRED':'BLOCKED';
        if($this->option('json'))$this->line(json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        else{$this->table(['Metric','Value'],collect($r['summary'])->map(fn($v,$k)=>[$k,$v])->values()->all());$this->info('status='.$r['status']);$this->line('database_mutations=0');}
        return $r['errors']===[]?self::SUCCESS:self::FAILURE;
    }
}
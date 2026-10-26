<?php

namespace Tests\\Feature;

use Illuminate\\Support\\Facades\\Artisan;
use Tests\\TestCase;

class ValidateOfferingGapAuditCountConsistencyTest extends TestCase
{
    public function test_summary_counts_must_match_record_classifications_and_blockers(): void
    {
        $path=tempnam(sys_get_temp_dir(),'offering-consistency-');
        $data=['summary'=>['record_count'=>2,'classification_counts'=>['BLOCKED'=>1,'READY'=>1],'blocker_count'=>1],'records'=>[
            ['classification'=>'BLOCKED','blockers'=>['missing_evidence'],'canonical_mutation_performed'=>false],
            ['classification'=>'READY','blockers'=>[],'canonical_mutation_performed'=>false],
        ],'mutation_performed'=>false,'database_mutations'=>0,'production_write_authorization'=>false];
        file_put_contents($path,json_encode($data));
        try{$exit=Artisan::call('institutes:offering-gap-artifact-validate',['file'=>$path,'--expected-rows'=>2,'--json'=>true]);$r=json_decode(Artisan::output(),true);$this->assertSame(0,$exit);$this->assertSame(['BLOCKED'=>1,'READY'=>1],$r['summary']['classification_counts']);$this->assertSame(1,$r['summary']['blocker_count_total']);}
        finally{@unlink($path);}
    }
    public function test_mismatched_source_summary_counts_block_artifact(): void
    {
        $path=tempnam(sys_get_temp_dir(),'offering-consistency-');
        $data=['summary'=>['record_count'=>9,'classification_counts'=>['BLOCKED'=>9],'blocker_count'=>0],'records'=>[['classification'=>'READY','blockers'=>[],'canonical_mutation_performed'=>false]],'mutation_performed'=>false,'database_mutations'=>0,'production_write_authorization'=>false];
        file_put_contents($path,json_encode($data));
        try{$exit=Artisan::call('institutes:offering-gap-artifact-validate',['file'=>$path,'--expected-rows'=>1,'--json'=>true]);$r=json_decode(Artisan::output(),true);$this->assertSame(1,$exit);$this->assertContains('summary_record_count_mismatch',$r['errors']);$this->assertContains('summary_classification_counts_mismatch',$r['errors']);}
        finally{@unlink($path);}
    }
}
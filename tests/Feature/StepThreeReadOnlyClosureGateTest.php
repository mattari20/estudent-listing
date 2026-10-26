<?php

namespace Tests\\Feature;

use Illuminate\\Support\\Facades\\Artisan;
use Tests\\TestCase;

class StepThreeReadOnlyClosureGateTest extends TestCase
{
    public function test_fresh_fully_reviewed_report_is_only_eligible_for_human_review(): void
    {
        $path=tempnam(sys_get_temp_dir(),'step3-gate-');
        $data=['checked_at'=>gmdate('c'),'records'=>[['review_status'=>'reviewed','reviewer_rationale'=>'Official source checked','canonical_mutation_performed'=>false]],'mutation_performed'=>false,'database_mutations'=>0,'production_write_authorization'=>false];
        file_put_contents($path,json_encode($data));
        try{$exit=Artisan::call('institutes:step3-closure-gate',['report'=>$path,'--expected-rows'=>1,'--json'=>true]);$r=json_decode(Artisan::output(),true);$this->assertSame(0,$exit);$this->assertSame('ELIGIBLE_FOR_HUMAN_CLOSURE_REVIEW',$r['status']);$this->assertFalse($r['closure_performed']);$this->assertSame(0,$r['database_mutations']);}
        finally{@unlink($path);}
    }
    public function test_stale_unreviewed_or_mutating_report_is_blocked(): void
    {
        $path=tempnam(sys_get_temp_dir(),'step3-gate-');
        $data=['checked_at'=>gmdate('c',time()-7200),'records'=>[['review_status'=>'pending','reviewer_rationale'=>'','canonical_mutation_performed'=>true]],'mutation_performed'=>true,'database_mutations'=>1,'production_write_authorization'=>true];
        file_put_contents($path,json_encode($data));
        try{$exit=Artisan::call('institutes:step3-closure-gate',['report'=>$path,'--expected-rows'=>1,'--max-age-hours'=>1,'--json'=>true]);$r=json_decode(Artisan::output(),true);$this->assertSame(1,$exit);$this->assertSame('BLOCKED',$r['status']);$this->assertContains('report_stale',$r['errors']);$this->assertContains('records_not_reviewed',$r['errors']);$this->assertFalse($r['closure_performed']);}
        finally{@unlink($path);}
    }
}
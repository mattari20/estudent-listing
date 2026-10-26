<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ValidateOfferingGapAuditArtifactTest extends TestCase
{
    public function test_valid_read_only_report_passes_but_remains_review_required(): void
    {
        $path=tempnam(sys_get_temp_dir(),'offering-artifact-');
        file_put_contents($path,json_encode(['summary'=>['register_rows'=>1],'records'=>[['classification'=>'BLOCKED','blockers'=>['evidence_missing'],'canonical_mutation_performed'=>false]],'mutation_performed'=>false,'database_mutations'=>0,'production_write_authorization'=>false,'reconciliation_status'=>'ROW_LEVEL_REPORT_CREATED_REVIEW_REQUIRED']));
        try {
            $exit=Artisan::call('institutes:offering-gap-artifact-validate',['file'=>$path,'--expected-rows'=>1,'--json'=>true]);
            $report=json_decode(Artisan::output(),true);
            $this->assertSame(0,$exit);
            $this->assertSame('ARTIFACT_VALID_REVIEW_REQUIRED',$report['status']);
            $this->assertSame(1,$report['summary']['record_count']);
            $this->assertFalse($report['mutation_performed']);
        } finally { @unlink($path); }
    }

    public function test_report_with_mutation_marker_or_count_mismatch_is_blocked(): void
    {
        $path=tempnam(sys_get_temp_dir(),'offering-artifact-');
        file_put_contents($path,json_encode(['summary'=>[],'records'=>[['classification'=>'candidate','blockers'=>[],'canonical_mutation_performed'=>true]],'mutation_performed'=>true,'database_mutations'=>1,'production_write_authorization'=>true]));
        try {
            $exit=Artisan::call('institutes:offering-gap-artifact-validate',['file'=>$path,'--expected-rows'=>36,'--json'=>true]);
            $report=json_decode(Artisan::output(),true);
            $this->assertSame(1,$exit);
            $this->assertSame('BLOCKED',$report['status']);
            $this->assertContains('mutation_marker_not_false',$report['errors']);
            $this->assertContains('database_mutations_not_zero',$report['errors']);
            $this->assertContains('record_count_mismatch',$report['errors']);
        } finally { @unlink($path); }
    }
}
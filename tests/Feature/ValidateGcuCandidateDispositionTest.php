<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ValidateGcuCandidateDispositionTest extends TestCase
{
    public function test_valid_artifact_passes_but_remains_review_only(): void
    {
        $path=tempnam(sys_get_temp_dir(),'gcu-disposition-');
        $h=fopen($path,'wb');
        fputcsv($h,['source_batch_id','import_row_id','row_number','candidate_name','normalized_slug','entity_type','source_url','snapshot_id','evidence_id','extractor_version','source_page_title','source_section_or_heading','source_excerpt','link_context','validation_errors','duplicate_kind','duplicate_target_import_row_id','current_row_status','disposition','reviewer_rationale','reviewer_id','reviewed_at']);
        fputcsv($h,['batch-1','row-1','1','BS Example','bs-example','program','https://example.edu/programs','snap-1','ev-1','v1','Programs','Undergraduate','BS Example','body','','','','needs_review','keep-for-review','Official page supports candidate','reviewer-1','2026-10-26']);
        fclose($h);
        try {
            $exit=Artisan::call('institutes:gcu-disposition-validate',[$path,'--expected-rows'=>1,'--json'=>true]);
            $report=json_decode(Artisan::output(),true);
            $this->assertSame(0,$exit);
            $this->assertSame('VALIDATION_PASSED_REVIEW_REQUIRED',$report['status']);
            $this->assertFalse($report['mutation_performed']);
            $this->assertSame(0,$report['database_mutations']);
        } finally { @unlink($path); }
    }

    public function test_duplicate_targets_must_exist_and_not_point_to_self(): void
    {
        $path=tempnam(sys_get_temp_dir(),'gcu-disposition-');
        $h=fopen($path,'wb');
        fputcsv($h,['source_batch_id','import_row_id','row_number','candidate_name','normalized_slug','entity_type','source_url','snapshot_id','extractor_version','disposition','duplicate_target_import_row_id','reviewer_rationale']);
        fputcsv($h,['batch-1','row-1','1','BS Example','bs-example','program','https://example.edu','snap-1','v1','duplicate-of-row','row-1','Duplicate']);
        fclose($h);
        try {
            $exit=Artisan::call('institutes:gcu-disposition-validate',[$path,'--expected-rows'=>1,'--json'=>true]);
            $report=json_decode(Artisan::output(),true);
            $this->assertSame(1,$exit);
            $this->assertContains('invalid_duplicate_target_for_row:row-1',$report['errors']);
            $this->assertSame(0,$report['database_mutations']);
        } finally { @unlink($path); }
    }
}
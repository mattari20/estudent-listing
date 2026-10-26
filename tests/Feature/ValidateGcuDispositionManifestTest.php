<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ValidateGcuDispositionManifestTest extends TestCase
{
    public function test_matching_manifest_checksum_and_row_count_pass_review_gate(): void
    {
        $dir=sys_get_temp_dir();$file=tempnam($dir,'gcu-csv-');$manifest=tempnam($dir,'gcu-manifest-');
        file_put_contents($file,"import_row_id,disposition\nrow-1,keep-for-review\n");
        file_put_contents($manifest,json_encode(['batch_id'=>'batch-1','source_file_sha256'=>hash_file('sha256',$file),'candidate_count'=>1,'generated_at'=>'2026-10-26T00:00:00Z']));
        try{$exit=Artisan::call('institutes:gcu-manifest-validate',['file'=>$file,'manifest'=>$manifest,'--expected-rows'=>1,'--json'=>true]);$r=json_decode(Artisan::output(),true);$this->assertSame(0,$exit);$this->assertSame('MANIFEST_VALID_REVIEW_REQUIRED',$r['status']);$this->assertTrue($r['summary']['checksum_matches']);$this->assertSame(0,$r['database_mutations']);}
        finally{@unlink($file);@unlink($manifest);}
    }
    public function test_modified_csv_fails_checksum_gate(): void
    {
        $dir=sys_get_temp_dir();$file=tempnam($dir,'gcu-csv-');$manifest=tempnam($dir,'gcu-manifest-');
        file_put_contents($file,"import_row_id,disposition\nrow-1,keep-for-review\n");
        file_put_contents($manifest,json_encode(['batch_id'=>'batch-1','source_file_sha256'=>str_repeat('0',64),'candidate_count'=>1,'generated_at'=>'2026-10-26T00:00:00Z']));
        try{$exit=Artisan::call('institutes:gcu-manifest-validate',['file'=>$file,'manifest'=>$manifest,'--expected-rows'=>1,'--json'=>true]);$r=json_decode(Artisan::output(),true);$this->assertSame(1,$exit);$this->assertContains('checksum_mismatch',$r['errors']);}
        finally{@unlink($file);@unlink($manifest);}
    }
}
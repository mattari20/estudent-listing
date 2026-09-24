<?php
namespace Tests\Feature;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Services\ImportQualityReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ImportQualityReportTest extends TestCase {
 use RefreshDatabase;
 public function test_quality_report_describes_batch_without_scoring_institutions(): void {
  $batch=ImportBatch::create(['batch_key'=>'quality-'.uniqid(),'entity_type'=>'institution','source_type'=>'manual','status'=>'validated','total_rows'=>4,'valid_rows'=>3,'invalid_rows'=>1,'candidate_rows'=>3,'received_at'=>now()]);
  ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'candidate','match_state'=>'new_candidate','payload'=>['name'=>'A']]);
  ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>2,'status'=>'promoted','match_state'=>'matched','payload'=>['name'=>'B']]);
  ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>3,'status'=>'invalid','match_state'=>'unresolved','payload'=>['name'=>'C']]);
  ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>4,'status'=>'candidate','match_state'=>'conflict','payload'=>['name'=>'D']]);
  $report=app(ImportQualityReportService::class)->build($batch);
  $this->assertSame(4,$report['total_rows']); $this->assertSame(1,$report['invalid_rows']); $this->assertSame(1,$report['promoted_rows']); $this->assertSame(1,$report['open_conflict_rows']);
 }
}
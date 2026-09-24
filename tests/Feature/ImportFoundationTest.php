<?php
namespace Tests\Feature;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ImportFoundationTest extends TestCase {
 use RefreshDatabase;
 public function test_import_entities_have_durable_batch_row_structures(): void {
  $source=Source::create(['source_type'=>'primary','name'=>'Test Source','status'=>'active']);
  $batch=ImportBatch::create(['batch_key'=>'test-batch','entity_type'=>'institution','source_type'=>'primary','source_id'=>$source->id,'status'=>'validated','total_rows'=>1,'valid_rows'=>1,'candidate_rows'=>1,'received_at'=>now(),'completed_at'=>now()]);
  ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>2,'status'=>'candidate','match_state'=>'unresolved','payload'=>['name'=>'Example University'],'normalized_payload'=>['name'=>'Example University','slug'=>'example-university']]);
  $this->assertDatabaseHas('import_batches',['batch_key'=>'test-batch','status'=>'validated','candidate_rows'=>1]);
  $this->assertDatabaseHas('import_rows',['row_number'=>2,'status'=>'candidate','match_state'=>'unresolved']);
  $this->assertDatabaseCount('institutions',0);
 }
 public function test_invalid_import_row_can_retain_validation_errors_without_canonical_change(): void {
  $source=Source::create(['source_type'=>'primary','name'=>'Test Source','status'=>'active']);
  $batch=ImportBatch::create(['batch_key'=>'invalid-batch','entity_type'=>'institution','source_type'=>'primary','source_id'=>$source->id,'status'=>'validated','total_rows'=>1,'invalid_rows'=>1]);
  ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>2,'status'=>'invalid','match_state'=>'unresolved','payload'=>['name'=>''],'validation_errors'=>['name is required']]);
  $this->assertDatabaseHas('import_rows',['row_number'=>2,'status'=>'invalid']);
  $this->assertDatabaseCount('institutions',0);
 }
}
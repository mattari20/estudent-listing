<?php
namespace Tests\Feature;
use App\Models\ImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
class ImportFoundationTest extends TestCase {
 use RefreshDatabase;
 public function test_import_creates_validation_batch_without_publishing_canonical_institution(): void {
  $csv="name,official_name,institution_type\nExample University,Example University of Pakistan,University\n";
  $response=$this->post('/institutes/import',['source_name'=>'Test Source','source_type'=>'primary','entity_type'=>'institution','csv'=>UploadedFile::fake()->createWithContent('institutes.csv',$csv)]);
  $response->assertOk()->assertSee('Import batch validated')->assertSee('1');
  $this->assertDatabaseCount('institutions',0);
  $this->assertDatabaseCount('import_rows',1);
  $this->assertDatabaseHas('import_rows',['status'=>'candidate']);
  $this->assertDatabaseHas('import_batches',['status'=>'validated','candidate_rows'=>1]);
 }
 public function test_import_rejects_duplicate_slugs_inside_batch(): void {
  $csv="name,slug\nExample University,example-university\nExample University Again,example-university\n";
  $this->post('/institutes/import',['source_name'=>'Test Source','source_type'=>'primary','entity_type'=>'institution','csv'=>UploadedFile::fake()->createWithContent('institutes.csv',$csv)])->assertOk();
  $this->assertDatabaseHas('import_rows',['row_number'=>2,'status'=>'invalid']);
 }
}
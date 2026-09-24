<?php
namespace Tests\Feature;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\User;
use Tests\TestCase;
class GeographyReviewAccessTest extends TestCase {
 public function test_public_cannot_access_geography_review_actions(): void {
  $batch=ImportBatch::create(['batch_key'=>'geo-access','entity_type'=>'geography','source_type'=>'test','status'=>'validated','received_at'=>now()]);
  $row=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'candidate','match_state'=>'unresolved','payload'=>['name'=>'X','slug'=>'x','type'=>'city'],'normalized_payload'=>['name'=>'X','slug'=>'x','type'=>'city']]);
  $this->post(route('institutes.operations.geography-resolve',$row))->assertRedirect();
 }
}
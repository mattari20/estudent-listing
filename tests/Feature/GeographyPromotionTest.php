<?php
namespace Tests\Feature;
use App\Models\Geography;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Services\GeographyPromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class GeographyPromotionTest extends TestCase {
 use RefreshDatabase;
 public function test_child_geography_promotes_only_with_existing_parent(): void {
  $parent=Geography::create(['name'=>'Punjab','slug'=>'punjab','type'=>'province','status'=>'active']);
  $batch=ImportBatch::create(['batch_key'=>'geo-test','entity_type'=>'geography','source_type'=>'test','status'=>'validated','received_at'=>now()]);
  $row=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'candidate','match_state'=>'new_candidate','payload'=>['name'=>'Lahore','slug'=>'lahore','type'=>'district','parent_slug'=>'punjab','parent_type'=>'province'],'normalized_payload'=>['name'=>'Lahore','slug'=>'lahore','type'=>'district','parent_slug'=>'punjab','parent_type'=>'province']]);
  $geo=(new GeographyPromotionService)->promote($row); $this->assertSame($parent->id,$geo->parent_id); $this->assertSame('promoted',$row->fresh()->status);
 }
}
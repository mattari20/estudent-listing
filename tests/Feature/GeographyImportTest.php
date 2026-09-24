<?php
namespace Tests\Feature;
use App\Models\Geography;
use App\Services\GeographyImportIntegrityService;
use App\Services\GeographyImportPreflightService;
use App\Services\GeographyImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class GeographyImportTest extends TestCase {
 use RefreshDatabase;
 public function test_invalid_type_is_rejected(): void { $this->assertNotEmpty((new GeographyImportService)->validateRow(['name'=>'X','slug'=>'x','type'=>'unknown'])); }
 public function test_duplicate_detection_is_local_to_batch(): void { $n=(new GeographyImportPreflightService)->duplicateCount([['slug'=>'lahore','type'=>'city'],['slug'=>'lahore','type'=>'city'],['slug'=>'lahore','type'=>'district']]); $this->assertSame(1,$n); }
 public function test_non_country_requires_active_parent(): void { $g=Geography::create(['name'=>'Punjab','slug'=>'punjab','type'=>'province','status'=>'active']); $this->assertTrue((new GeographyImportIntegrityService)->validParent('district',$g)); }
}
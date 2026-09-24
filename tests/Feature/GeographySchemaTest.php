<?php
namespace Tests\Feature;
use App\Services\GeographyImportBatchService;
use App\Services\GeographySchemaProfileService;
use Tests\TestCase;
class GeographySchemaTest extends TestCase {
 public function test_profile_contains_governed_parent_columns(): void { $p=(new GeographySchemaProfileService)->geography(); $this->assertContains('parent_slug',$p->columns); $this->assertContains('parent_type',$p->required_columns); }
 public function test_batch_normalization_is_noncanonical(): void { $r=(new GeographyImportBatchService)->normalize(['name'=>' Lahore ','slug'=>' Lahore ']); $this->assertSame('lahore',$r['slug']); $this->assertSame('Lahore',$r['name']); }
 public function test_child_requires_parent_reference(): void { $this->assertContains('parent',(new GeographyImportBatchService)->validate(['name'=>'Lahore','slug'=>'lahore','type'=>'city'])); }
}
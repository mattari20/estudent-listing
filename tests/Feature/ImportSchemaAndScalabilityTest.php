<?php
namespace Tests\Feature;
use App\Models\ImportSchemaProfile;
use App\Services\ImportSchemaProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ImportSchemaAndScalabilityTest extends TestCase {
 use RefreshDatabase;
 public function test_institution_schema_profile_is_versioned(): void {
  $p=app(ImportSchemaProfileService::class)->institution('1.0');
  $this->assertInstanceOf(ImportSchemaProfile::class,$p); $this->assertSame(['name'],$p->required_columns); $this->assertContains('official_name',$p->columns);
 }
 public function test_schema_rejects_unknown_and_missing_columns(): void {
  $s=app(ImportSchemaProfileService::class); $p=$s->institution();
  $this->assertNotEmpty($s->validateHeaders(['name','unknown'],$p)); $this->assertNotEmpty($s->validateHeaders(['official_name'],$p));
 }
}
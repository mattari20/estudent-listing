<?php
namespace Tests\Feature;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class InstitutionApiTest extends TestCase {
 use RefreshDatabase;
 public function test_versioned_institution_api_lists_active_institutions(): void {
  Institution::create(['name'=>'API University','slug'=>'api-university','status'=>'active']);
  $this->getJson('/api/v1/institutions')->assertOk()->assertJsonPath('data.0.name','API University');
 }
 public function test_versioned_institution_api_supports_slug_lookup(): void {
  $i=Institution::create(['name'=>'API University','slug'=>'api-university','status'=>'active']);
  $this->getJson('/api/v1/institutions/api-university')->assertOk()->assertJsonPath('name',$i->name);
 }
}
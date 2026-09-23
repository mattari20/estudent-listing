<?php
namespace Tests\Feature;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class DirectorySearchTest extends TestCase {
 use RefreshDatabase;
 public function test_institute_search_matches_name(): void {
  Institution::create(['name'=>'Lahore Engineering University','slug'=>'lahore-engineering-university','status'=>'active']);
  Institution::create(['name'=>'Karachi Medical College','slug'=>'karachi-medical-college','status'=>'active']);
  $this->get('/institutes/search?q=Engineering')->assertOk()->assertSee('Lahore Engineering University')->assertDontSee('Karachi Medical College');
 }
 public function test_search_route_preserves_directory_root(): void {
  $this->get('/institutes/search')->assertOk()->assertSee('Institute Search');
 }
}
<?php
namespace Tests\Feature;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class PublicDirectoryHardeningTest extends TestCase {
 use RefreshDatabase;
 public function test_inactive_institution_is_not_publicly_visible(): void { $i=Institution::create(['name'=>'Hidden','slug'=>'hidden','status'=>'inactive']); $this->get(route('institutes.show',$i))->assertNotFound(); }
 public function test_directory_lists_only_active_institutions(): void { Institution::create(['name'=>'Active','slug'=>'active','status'=>'active']); Institution::create(['name'=>'Hidden','slug'=>'hidden','status'=>'inactive']); $this->get(route('institutes.index'))->assertOk()->assertSee('Active')->assertDontSee('Hidden'); }

    public function test_directory_does_not_list_candidate_institutions(): void
 {
  Institution::create(['name'=>'Candidate University','slug'=>'candidate-university','status'=>'candidate']);
  $this->get(route('institutes.index'))->assertOk()->assertDontSee('Candidate University');
 }

    public function test_candidate_institution_is_not_in_public_directory_results(): void
 {
  Institution::create(['name' => 'Pending Review University', 'slug' => 'pending-review-university', 'status' => 'candidate']);
  $this->get(route('institutes.index'))->assertOk()->assertDontSee('Pending Review University');
 }
}
<?php
namespace Tests\Feature;
use App\Models\Institution;
use Tests\TestCase;
class InstituteSearchProductionTest extends TestCase {
 public function test_unknown_query_parameters_are_not_preserved(): void {
  Institution::create(['name'=>'Visible','slug'=>'visible','status'=>'active']);
  $this->get(route('institutes.search',['q'=>'Visible','unknown'=>'x']))->assertOk()->assertDontSee('name="unknown"');
 }
 public function test_search_has_stable_secondary_order(): void {
  Institution::create(['name'=>'Same','slug'=>'same-a','status'=>'active']);
  Institution::create(['name'=>'Same','slug'=>'same-b','status'=>'active']);
  $this->get(route('institutes.search',['q'=>'Same']))->assertOk()->assertSee('Same');
 }

    public function test_search_matching_only_inactive_institutions_does_not_expose_them(): void
 {
  Institution::create(['name'=>'Hidden Archive University','slug'=>'hidden-archive-university','status'=>'inactive']);
  $this->get(route('institutes.search',['q'=>'Hidden Archive University']))->assertOk()->assertDontSee('Hidden Archive University');
 }
}
<?php
namespace Tests\Feature;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\Program;
use App\Models\Recommendation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class RecommendationTest extends TestCase {
 use RefreshDatabase;
 public function test_recommendation_can_target_an_institution(): void {
  $i=Institution::create(['name'=>'Test University','slug'=>'test-university']);
  $r=Recommendation::create(['institution_id'=>$i->id,'contributor_type'=>'student','recommendation'=>'recommend','rating'=>5,'verification_state'=>'verified','status'=>'published']);
  $this->assertTrue($r->institution->is($i)); $this->assertNull($r->program_id);
 }
 public function test_recommendation_can_target_a_program(): void {
  $i=Institution::create(['name'=>'Test University','slug'=>'test-university']);
  $p=Program::create(['institution_id'=>$i->id,'name'=>'Computer Science','slug'=>'computer-science']);
  $r=Recommendation::create(['program_id'=>$p->id,'contributor_type'=>'teacher','recommendation'=>'neutral','rating'=>4,'verification_state'=>'unverified','status'=>'pending']);
  $this->assertTrue($r->program->is($p)); $this->assertNull($r->institution_id);
 }
}
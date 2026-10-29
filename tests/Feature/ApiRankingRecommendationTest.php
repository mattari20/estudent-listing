<?php
namespace Tests\Feature;
use App\Models\Institution;
use App\Models\RankingMethodology;
use App\Models\RecommendationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ApiRankingRecommendationTest extends TestCase {
 use RefreshDatabase;
 public function test_institution_api_returns_active_institutions(): void {
  Institution::create(['name'=>'API University','slug'=>'api-university','status'=>'active']);
  $this->getJson('/institutes/api/v1/institutions')->assertOk()->assertJsonPath('data.0.name','API University');
 }
 public function test_ranking_methodology_is_versioned(): void {$m=RankingMethodology::create(['name'=>'Directory Ranking Framework','version'=>'1.0','status'=>'draft']);$this->assertSame('1.0',$m->version);}
 public function test_recommendation_policy_is_versioned(): void {$p=RecommendationPolicy::create(['name'=>'Program Recommendation Framework','version'=>'1.0','status'=>'draft']);$this->assertSame('1.0',$p->version);}
}
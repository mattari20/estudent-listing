<?php
namespace Tests\Feature;
use App\Models\Institution; use App\Services\InstituteSearchRelevanceService; use Tests\TestCase;
class InstituteSearchRelevanceTest extends TestCase { public function test_exact_name_has_highest_local_score(): void { $i=new Institution(['name'=>'University of Lahore','official_name'=>'University of Lahore','short_name'=>'UOL']); $s=new InstituteSearchRelevanceService; $this->assertSame(100,$s->score($i,'University of Lahore')); } public function test_empty_query_has_no_relevance_score(): void { $i=new Institution(['name'=>'Example']); $this->assertSame(0,(new InstituteSearchRelevanceService)->score($i,'')); } 
    public function test_exact_name_relevance_is_case_insensitive(): void { $i=new Institution(['name'=>'University of Lahore','official_name'=>'University of Lahore']); $s=new InstituteSearchRelevanceService; $this->assertSame(100,$s->score($i,'university of lahore')); }

    public function test_short_name_query_scores_above_unrelated_query(): void
 {
  $institution = new Institution(['name' => 'University of Lahore', 'official_name' => 'University of Lahore', 'short_name' => 'UOL']);
  $service = new InstituteSearchRelevanceService;
  $this->assertGreaterThan($service->score($institution, 'xyz unrelated'), $service->score($institution, 'UOL'));
 }
}
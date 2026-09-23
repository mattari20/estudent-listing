<?php
namespace Tests\Feature;
use App\Models\ExternalRanking;
use App\Models\Institution;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ExternalRankingTest extends TestCase {
 use RefreshDatabase;
 public function test_external_ranking_can_be_scoped_to_an_institution(): void {
  $institution=Institution::create(['name'=>'Test University','slug'=>'test-university']);
  $ranking=ExternalRanking::create(['institution_id'=>$institution->id,'publisher_name'=>'Example Publisher','ranking_name'=>'Example World Ranking','edition'=>'2026','year'=>2026,'rank_value'=>125,'signal_type'=>'ranking','status'=>'verified']);
  $this->assertTrue($ranking->institution->is($institution)); $this->assertNull($ranking->program_id);
 }
 public function test_external_ranking_can_be_scoped_to_a_program(): void {
  $institution=Institution::create(['name'=>'Test University','slug'=>'test-university']);
  $program=Program::create(['institution_id'=>$institution->id,'name'=>'Computer Science','slug'=>'computer-science']);
  $ranking=ExternalRanking::create(['program_id'=>$program->id,'publisher_name'=>'Example Publisher','ranking_name'=>'Example Subject Ranking','edition'=>'2026','year'=>2026,'rank_label'=>'101-150','signal_type'=>'ranking','status'=>'verified']);
  $this->assertTrue($ranking->program->is($program)); $this->assertNull($ranking->institution_id);
 }
}
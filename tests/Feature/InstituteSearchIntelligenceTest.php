<?php
namespace Tests\Feature;
use App\Services\InstituteSearchQueryParser; use App\Services\InstituteSearchZeroResultService; use Tests\TestCase;
class InstituteSearchIntelligenceTest extends TestCase { public function test_parser_trims_inputs(): void { $this->assertSame('lahore',(new InstituteSearchQueryParser)->parse(['q'=>'  lahore '])['q']); } public function test_zero_result_recovery_is_bounded(): void { $r=(new InstituteSearchZeroResultService)->suggestions(['q'=>'x','city'=>'Lahore']); $this->assertSame(2,$r['active_filter_count']); } }
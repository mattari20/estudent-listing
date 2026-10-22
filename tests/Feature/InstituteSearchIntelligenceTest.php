<?php
namespace Tests\Feature;
use App\Services\InstituteSearchQueryParser; use App\Services\InstituteSearchZeroResultService; use Tests\TestCase;
class InstituteSearchIntelligenceTest extends TestCase { public function test_parser_trims_inputs(): void { $this->assertSame('lahore',(new InstituteSearchQueryParser)->parse(['q'=>'  lahore '])['q']); } public function test_zero_result_recovery_is_bounded(): void { $r=(new InstituteSearchZeroResultService)->suggestions(['q'=>'x','city'=>'Lahore']); $this->assertSame(2,$r['active_filter_count']); } 
    public function test_parser_normalizes_whitespace_only_query_to_empty_string(): void { $this->assertSame('',(new InstituteSearchQueryParser)->parse(['q'=>'   '])['q']); }

    public function test_missing_query_is_normalized_to_empty_string(): void { $this->assertSame('', (new InstituteSearchQueryParser)->parse([])['q']); }
}
<?php
namespace Tests\Feature;
use App\Services\InstituteSearchContextService; use Tests\TestCase;
class InstituteSearchContextTest extends TestCase { public function test_context_detects_query(): void { $this->assertTrue((new InstituteSearchContextService)->detect('Lahore')['has_query']); } 
    public function test_context_marks_blank_query_as_no_query(): void
 {
  $context=(new InstituteSearchContextService)->detect('   ');
  $this->assertFalse($context['has_query']);
 }

    public function test_mixed_case_location_query_is_detected(): void
 {
  $context = (new InstituteSearchContextService)->detect('LaHoRe');
  $this->assertTrue($context['has_query']);
 }
}
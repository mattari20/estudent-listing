<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AdmissionsEligibilityTest extends TestCase
{
    use RefreshDatabase;
    public function test_admission_and_eligibility_tables_migrate(): void
    {
        $this->assertDatabaseCount('admission_intakes', 0);
        $this->assertDatabaseCount('eligibility_rules', 0);
    }
}

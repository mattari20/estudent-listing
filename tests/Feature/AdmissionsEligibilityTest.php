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

    public function test_eligibility_rules_support_auditable_rule_fields(): void
    {
        foreach (['admission_intake_id', 'rule_type', 'applicant_category', 'qualification', 'requirement', 'minimum_marks', 'required_subject', 'entry_test_required', 'domicile_requirement', 'status'] as $column) {
            $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('eligibility_rules', $column), "Missing eligibility_rules column: {$column}");
        }
    }
}

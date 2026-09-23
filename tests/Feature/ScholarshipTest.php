<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ScholarshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_scholarships_table_supports_optional_financial_aid_context(): void
    {
        $this->assertTrue(Schema::hasTable('scholarships'));

        foreach ([
            'institution_id','program_id','program_offering_id','campus_id',
            'admission_intake_id','name','scholarship_type','eligibility_basis',
            'eligibility_description','benefit_type','amount_min','amount_max',
            'currency','benefit_frequency','duration','renewal_condition',
            'application_open','application_close','data_state','status',
            'effective_from','effective_until','notes',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('scholarships', $column),
                "Missing scholarships column: {$column}"
            );
        }
    }
}

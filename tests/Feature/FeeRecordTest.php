<?php

namespace Tests\\Feature;

use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Illuminate\\Support\\Facades\\Schema;
use Tests\\TestCase;

class FeeRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_fee_records_table_supports_optional_financial_context(): void
    {
        $this->assertTrue(Schema::hasTable('fee_records'));

        foreach ([
            'institution_id','program_id','program_offering_id','campus_id',
            'admission_intake_id','fee_type','data_state','amount_min','amount_max',
            'currency','unit','period','payment_stage','applicant_category',
            'delivery_mode','effective_from','effective_until','notes',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('fee_records', $column),
                "Missing fee_records column: {$column}"
            );
        }
    }
}

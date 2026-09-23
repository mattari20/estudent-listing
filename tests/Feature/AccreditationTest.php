<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccreditationTest extends TestCase
{
    use RefreshDatabase;

    public function test_accreditations_table_supports_scoped_recognition_data(): void
    {
        $this->assertTrue(Schema::hasTable('accreditations'));

        foreach ([
            'institution_id','program_id','program_offering_id','campus_id',
            'name','recognition_type','authority_name','scope_type',
            'reference_identifier','status','effective_from','effective_until',
            'last_verified_at','notes',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('accreditations', $column),
                "Missing accreditations column: {$column}"
            );
        }
    }
}

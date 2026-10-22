<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ProgramDomainTest extends TestCase
{
    use RefreshDatabase;
    public function test_program_and_offering_tables_migrate(): void
    {
        $this->assertDatabaseCount('programs', 0);
        $this->assertDatabaseCount('program_offerings', 0);
    }

    public function test_program_offerings_support_canonical_parent_and_status_fields(): void
    {
        foreach (['program_id', 'institution_id', 'campus_id', 'status'] as $column) {
            $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('program_offerings', $column), "Missing program_offerings column: {$column}");
        }
    }
}

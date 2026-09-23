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
}

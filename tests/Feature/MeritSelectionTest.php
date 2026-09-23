<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MeritSelectionTest extends TestCase
{
    use RefreshDatabase;
    public function test_merit_and_selection_tables_migrate(): void
    {
        $this->assertDatabaseCount('merit_records', 0);
        $this->assertDatabaseCount('selection_outcomes', 0);
    }
}

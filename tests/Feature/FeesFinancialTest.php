<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class FeesFinancialTest extends TestCase
{
    use RefreshDatabase;
    public function test_fee_table_migrates_and_is_optional(): void
    {
        $this->assertDatabaseCount('fee_records', 0);
        $this->assertDatabaseMissing('fee_records', ['status' => 'unknown']);
    }
}

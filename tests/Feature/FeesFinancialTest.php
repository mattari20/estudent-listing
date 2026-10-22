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

    public function test_fee_records_support_amount_currency_and_source_context(): void
    {
        foreach (['amount_min', 'amount_max', 'currency', 'status', 'source_id', 'last_verified_at'] as $column) {
            $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('fee_records', $column), "Missing fee_records column: {$column}");
        }
    }
}

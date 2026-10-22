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

    public function test_fee_records_support_amount_currency_and_intake_context(): void
    {
        foreach (['program_offering_id', 'admission_intake_id', 'fee_type', 'amount', 'minimum_amount', 'maximum_amount', 'currency', 'frequency', 'status'] as $column) {
            $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('fee_records', $column), "Missing fee_records column: {$column}");
        }
    }
}

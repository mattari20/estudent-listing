<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RefreshInstituteDiscoveryBatchGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_requires_explicit_confirmation_before_creating_a_batch(): void
    {
        $exit = Artisan::call('institutes:refresh-discovery', [
            'profile' => 'gcu-lahore-programs',
            '--supersedes-batch-id' => 1,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('No batch was created', Artisan::output());
        $this->assertSame(0, ImportBatch::count());
    }

    public function test_refresh_rejects_unknown_profile_without_creating_a_batch(): void
    {
        $exit = Artisan::call('institutes:refresh-discovery', [
            'profile' => 'profile-that-does-not-exist',
            '--confirm-new-batch' => true,
            '--supersedes-batch-id' => 1,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Unknown discovery profile', Artisan::output());
        $this->assertSame(0, ImportBatch::count());
    }

    public function test_refresh_requires_a_real_prior_batch_and_preserves_existing_batches(): void
    {
        $batch = ImportBatch::create([
            'batch_key' => 'refresh-guard-existing',
            'entity_type' => 'institution',
            'source_type' => 'manual',
            'status' => 'completed',
            'total_rows' => 0,
        ]);

        $exit = Artisan::call('institutes:refresh-discovery', [
            'profile' => 'gcu-lahore-programs',
            '--confirm-new-batch' => true,
            '--supersedes-batch-id' => 999999,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('prior batch does not exist', Artisan::output());
        $this->assertSame(1, ImportBatch::count());
        $this->assertDatabaseHas('import_batches', ['id' => $batch->id, 'batch_key' => 'refresh-guard-existing']);
    }
}
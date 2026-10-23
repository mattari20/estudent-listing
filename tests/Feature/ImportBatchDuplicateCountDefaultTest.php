<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportBatchDuplicateCountDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_import_batch_starts_with_zero_duplicate_rows(): void
    {
        $batch = ImportBatch::create([
            'batch_key' => uniqid('duplicate-default-', true),
            'entity_type' => 'campus',
            'source_type' => 'official',
            'status' => 'received',
        ]);

        $this->assertSame(0, $batch->fresh()->duplicate_rows);
    }
}

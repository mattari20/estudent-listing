<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Source;
use App\Services\ImportDuplicateDetectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportDuplicateDetectorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_ignores_only_the_explicitly_superseded_batch(): void
    {
        $institution = Institution::create([
            'name' => 'Test University',
            'slug' => 'test-university',
            'status' => 'active',
        ]);
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Official programme source',
            'url' => 'https://example.edu/programmes',
            'authority_scope' => 'official programme listing',
            'status' => 'active',
        ]);

        $supersededBatch = ImportBatch::create([
            'batch_key' => 'agent-test-old',
            'entity_type' => 'program',
            'source_type' => 'automated_discovery',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 1,
            'received_at' => now(),
        ]);
        $otherBatch = ImportBatch::create([
            'batch_key' => 'agent-test-other',
            'entity_type' => 'program',
            'source_type' => 'automated_discovery',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 1,
            'received_at' => now(),
        ]);

        $makeRow = function (ImportBatch $batch, int $rowNumber) use ($institution): ImportRow {
            return ImportRow::create([
                'import_batch_id' => $batch->id,
                'row_number' => $rowNumber,
                'status' => 'candidate',
                'match_state' => 'unresolved',
                'parent_subject_type' => Institution::class,
                'parent_subject_id' => $institution->id,
                'payload' => ['name' => 'Computer Science', 'slug' => 'computer-science'],
                'normalized_payload' => ['name' => 'Computer Science', 'slug' => 'computer-science'],
                'validation_errors' => null,
            ]);
        };

        $makeRow($supersededBatch, 1);
        $otherRow = $makeRow($otherBatch, 1);

        $duplicate = app(ImportDuplicateDetectorService::class)->find(
            'program',
            $institution->id,
            'computer-science',
            null,
            [$supersededBatch->id]
        );

        $this->assertSame('import', $duplicate['kind']);
        $this->assertSame($otherRow->id, $duplicate['id']);
    }
}

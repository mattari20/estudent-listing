<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportPromotionDecision;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Services\BulkImportPromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkCampusPromotionTest extends TestCase
{
    use RefreshDatabase;

    private function candidateCampus(): array
    {
        $institution = Institution::query()->create([
            'name' => 'Bulk Campus Test University',
            'slug' => 'bulk-campus-test-university',
            'institution_type' => 'university',
            'status' => 'active',
        ]);

        $batch = ImportBatch::query()->create([
            'batch_key' => 'bulk-campus-' . uniqid(),
            'entity_type' => 'campus',
            'source_type' => 'test',
            'status' => 'validated',
            'total_rows' => 1,
            'valid_rows' => 1,
            'candidate_rows' => 1,
            'received_at' => now(),
            'completed_at' => now(),
        ]);

        $row = ImportRow::query()->create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'unresolved',
            'parent_subject_type' => Institution::class,
            'parent_subject_id' => $institution->id,
            'payload' => ['name' => 'Main Campus'],
            'normalized_payload' => [
                'name' => 'Main Campus',
                'slug' => 'main-campus',
                'campus_type' => 'main',
                'status' => 'active',
            ],
        ]);

        return [$institution, $row];
    }

    public function test_approved_campus_candidate_is_promoted_by_bulk_promotion(): void
    {
        [$institution, $row] = $this->candidateCampus();

        ImportPromotionDecision::query()->create([
            'import_row_id' => $row->id,
            'decision' => 'approved',
            'actor_type' => 'user',
            'actor_id' => 1,
            'reason' => 'Explicit test approval',
            'decided_at' => now(),
        ]);

        $result = app(BulkImportPromotionService::class)->promote([$row->id], 1);

        $this->assertSame(['promoted' => 1, 'skipped' => 0, 'failed' => 0], $result);
        $this->assertSame('promoted', $row->fresh()->status);
        $this->assertDatabaseHas('campuses', [
            'institution_id' => $institution->id,
            'slug' => 'main-campus',
            'name' => 'Main Campus',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('import_promotion_events', [
            'import_row_id' => $row->id,
            'event_type' => 'promotion_succeeded',
        ]);
    }

    public function test_campus_candidate_without_approval_is_not_promoted(): void
    {
        [, $row] = $this->candidateCampus();

        $result = app(BulkImportPromotionService::class)->promote([$row->id], 1);

        $this->assertSame(['promoted' => 0, 'skipped' => 1, 'failed' => 0], $result);
        $this->assertSame('candidate', $row->fresh()->status);
        $this->assertDatabaseCount('campuses', 0);
    }
}

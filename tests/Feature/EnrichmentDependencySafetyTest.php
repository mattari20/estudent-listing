<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportPromotionDecision;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Program;
use App\Services\EnrichmentPromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class EnrichmentDependencySafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_requires_institution_parent(): void
    {
        $batch = ImportBatch::create(['batch_key' => 'dep-program', 'entity_type' => 'program', 'source_type' => 'primary']);
        $row = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'payload' => ['name' => 'BS CS'],
            'normalized_payload' => ['name' => 'BS CS'],
        ]);

        $this->expectException(RuntimeException::class);
        app(EnrichmentPromotionService::class)->validateParent($row);
    }

    public function test_offering_rejects_institution_as_parent(): void
    {
        $batch = ImportBatch::create(['batch_key' => 'dep-offering', 'entity_type' => 'program_offering', 'source_type' => 'primary']);
        $institution = Institution::create(['name' => 'Example', 'slug' => 'example', 'status' => 'active']);
        $row = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'payload' => [],
            'normalized_payload' => [],
            'parent_subject_type' => Institution::class,
            'parent_subject_id' => $institution->id,
        ]);

        $this->expectException(RuntimeException::class);
        app(EnrichmentPromotionService::class)->validateParent($row);
    }

    public function test_offering_accepts_existing_program_parent(): void
    {
        $batch = ImportBatch::create(['batch_key' => 'dep-offering-valid', 'entity_type' => 'program_offering', 'source_type' => 'primary']);
        $institution = Institution::create(['name' => 'Example', 'slug' => 'example', 'status' => 'active']);
        $program = Program::create([
            'institution_id' => $institution->id,
            'name' => 'BS CS',
            'slug' => 'bs-cs',
            'status' => 'active',
        ]);
        $row = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'payload' => [],
            'normalized_payload' => [],
            'parent_subject_type' => Program::class,
            'parent_subject_id' => $program->id,
        ]);

        app(EnrichmentPromotionService::class)->validateParent($row);
        $this->assertTrue(true);
    }

    public function test_campus_rejects_nonexistent_institution_parent_before_approval(): void
    {
        $batch = ImportBatch::create(['batch_key' => 'dep-campus-missing-parent', 'entity_type' => 'campus', 'source_type' => 'primary']);
        $row = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'payload' => ['name' => 'Synthetic Campus'],
            'normalized_payload' => ['name' => 'Synthetic Campus'],
            'parent_subject_type' => Institution::class,
            'parent_subject_id' => PHP_INT_MAX,
        ]);

        try {
            app(EnrichmentPromotionService::class)->validateParent($row);
            $this->fail('A missing canonical institution must not pass parent validation.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'The selected canonical Institution parent does not exist. Refresh parent resolution and select an existing canonical record before approval or promotion.',
                $exception->getMessage()
            );
        }

        $this->assertSame('candidate', $row->fresh()->status);
        $this->assertDatabaseCount('import_promotion_decisions', 0);
    }

    public function test_offering_rejects_nonexistent_program_parent_before_approval(): void
    {
        $batch = ImportBatch::create(['batch_key' => 'dep-offering-missing-parent', 'entity_type' => 'program_offering', 'source_type' => 'primary']);
        $row = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'payload' => ['delivery_mode' => 'on_campus'],
            'normalized_payload' => ['delivery_mode' => 'on_campus'],
            'parent_subject_type' => Program::class,
            'parent_subject_id' => PHP_INT_MAX,
        ]);

        try {
            app(EnrichmentPromotionService::class)->validateParent($row);
            $this->fail('A missing canonical program must not pass parent validation.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'The selected canonical Program parent does not exist. Refresh parent resolution and select an existing canonical record before approval or promotion.',
                $exception->getMessage()
            );
        }

        $this->assertSame('candidate', $row->fresh()->status);
        $this->assertDatabaseCount('import_promotion_decisions', 0);
    }

    public function test_enrichment_has_separate_promotion_decision_record(): void
    {
        $batch = ImportBatch::create(['batch_key' => 'dep-decision', 'entity_type' => 'program', 'source_type' => 'primary']);
        $row = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'payload' => ['name' => 'BS CS'],
            'normalized_payload' => ['name' => 'BS CS'],
        ]);
        ImportPromotionDecision::create([
            'import_row_id' => $row->id,
            'decision' => 'approved',
            'actor_type' => 'user',
            'actor_id' => 1,
            'reason' => 'Reviewed',
            'decided_at' => now(),
        ]);

        $this->assertDatabaseHas('import_promotion_decisions', ['import_row_id' => $row->id, 'decision' => 'approved']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\EnrichmentImportRow;
use App\Models\ImportBatch;
use App\Models\Institution;
use App\Services\StructuredEnrichmentPromoter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampusPromotionIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_repromoting_campus_with_same_institution_and_slug_updates_one_canonical_row(): void
    {
        $institution = Institution::create([
            'name' => 'Campus Idempotency University',
            'slug' => 'campus-idempotency-university',
            'status' => 'active',
        ]);

        $batch = ImportBatch::create([
            'batch_key' => uniqid('campus-idempotency-', true),
            'entity_type' => 'enrichment',
            'source_type' => 'primary',
            'status' => 'validated',
        ]);

        $row = EnrichmentImportRow::create([
            'import_batch_id' => $batch->id,
            'entity_type' => 'campus',
            'status' => 'approved',
            'match_state' => 'matched',
            'payload' => ['institution_id' => $institution->id, 'name' => 'Main Campus'],
            'normalized_payload' => [
                'institution_id' => $institution->id,
                'name' => 'Main Campus',
                'slug' => 'main-campus',
                'status' => 'active',
            ],
        ]);

        $promoter = app(StructuredEnrichmentPromoter::class);
        $first = $promoter->promote($row);

        $row->forceFill([
            'normalized_payload' => [
                'institution_id' => $institution->id,
                'name' => 'Main Campus - Updated',
                'slug' => 'main-campus',
                'status' => 'active',
            ],
        ])->save();

        $second = $promoter->promote($row->fresh());

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Campus::where('institution_id', $institution->id)->where('slug', 'main-campus')->count());
        $this->assertDatabaseHas('campuses', [
            'id' => $first->id,
            'name' => 'Main Campus - Updated',
        ]);
    }
}

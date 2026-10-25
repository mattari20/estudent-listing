<?php

namespace Tests\Feature;

use App\Models\EnrichmentImportRow;
use App\Models\ImportBatch;
use App\Models\StructuredEnrichmentPromotionEvent;
use App\Models\Institution;
use App\Services\StructuredEnrichmentPromoter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructuredEnrichmentPromotionIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_repromoting_same_approved_program_updates_canonical_row_instead_of_duplicating_it(): void
    {
        $institution = Institution::create([
            'name' => 'Idempotency Test University',
            'slug' => 'idempotency-test-university',
            'status' => 'active',
        ]);

        $batch = ImportBatch::create([
            'batch_key' => uniqid('idempotency-', true),
            'entity_type' => 'enrichment',
            'source_type' => 'primary',
            'status' => 'validated',
        ]);

        $row = EnrichmentImportRow::create([
            'import_batch_id' => $batch->id,
            'entity_type' => 'program',
            'status' => 'approved',
            'match_state' => 'matched',
            'payload' => [
                'institution_id' => $institution->id,
                'name' => 'BS Computer Science',
            ],
            'normalized_payload' => [
                'institution_id' => $institution->id,
                'name' => 'BS Computer Science',
                'slug' => 'bs-computer-science',
            ],
        ]);

        $promoter = app(StructuredEnrichmentPromoter::class);
        $first = $promoter->promote($row);

        $row->forceFill([
            'normalized_payload' => [
                'institution_id' => $institution->id,
                'name' => 'Bachelor of Science in Computer Science',
                'slug' => 'bs-computer-science',
            ],
        ])->save();

        $second = $promoter->promote($row->fresh());

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('programs', 1);
        $this->assertDatabaseHas('programs', [
            'id' => $first->id,
            'institution_id' => $institution->id,
            'slug' => 'bs-computer-science',
            'name' => 'Bachelor of Science in Computer Science',
        ]);

        $this->assertDatabaseCount('structured_enrichment_promotion_events', 2);
        $latestEvent = StructuredEnrichmentPromotionEvent::query()->orderByDesc('id')->firstOrFail();
        $this->assertSame('promotion_succeeded', $latestEvent->event_type);
        $this->assertSame($row->id, $latestEvent->enrichment_import_row_id);
        $this->assertSame($first->id, $latestEvent->canonical_id);
        $this->assertSame('BS Computer Science', $latestEvent->before_snapshot['name']);
        $this->assertSame('Bachelor of Science in Computer Science', $latestEvent->after_snapshot['name']);
    }
}

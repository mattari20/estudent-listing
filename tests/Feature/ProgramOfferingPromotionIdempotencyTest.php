<?php

namespace Tests\Feature;

use App\Models\EnrichmentImportRow;
use App\Models\ImportBatch;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Services\StructuredEnrichmentPromoter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramOfferingPromotionIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_repromoting_same_offering_identity_updates_existing_row(): void
    {
        $institution = Institution::create([
            'name' => 'Offering Idempotency University',
            'slug' => 'offering-idempotency-university',
            'status' => 'active',
        ]);
        $program = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Offering Idempotency Program',
            'slug' => 'offering-idempotency-program',
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => uniqid('offering-idempotency-', true),
            'entity_type' => 'enrichment',
            'source_type' => 'primary',
            'status' => 'validated',
        ]);
        $row = EnrichmentImportRow::create([
            'import_batch_id' => $batch->id,
            'entity_type' => 'offering',
            'status' => 'approved',
            'match_state' => 'matched',
            'payload' => [],
            'normalized_payload' => [
                'program_id' => $program->id,
                'campus_id' => null,
                'delivery_mode' => 'online',
                'intake_label' => 'Fall',
                'status' => 'active',
                'availability' => 'unknown',
            ],
        ]);

        $promoter = app(StructuredEnrichmentPromoter::class);
        $first = $promoter->promote($row);
        $row->forceFill([
            'normalized_payload' => [
                'program_id' => $program->id,
                'campus_id' => null,
                'delivery_mode' => 'online',
                'intake_label' => 'Fall',
                'status' => 'inactive',
                'availability' => 'unknown',
            ],
        ])->save();
        $second = $promoter->promote($row->fresh());

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, ProgramOffering::where('program_id', $program->id)->count());
        $this->assertDatabaseHas('program_offerings', [
            'id' => $first->id,
            'status' => 'inactive',
        ]);
    }
}

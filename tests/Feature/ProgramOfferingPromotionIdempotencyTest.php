<?php

namespace Tests\Feature;

use App\Models\EnrichmentImportRow;
use App\Models\ImportBatch;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Models\StructuredEnrichmentPromotionEvent;
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
        $this->assertDatabaseCount('structured_enrichment_promotion_events', 2);
        $latestEvent = StructuredEnrichmentPromotionEvent::query()->orderByDesc('id')->firstOrFail();
        $this->assertSame('offering', $latestEvent->entity_type);
        $this->assertSame($first->id, $latestEvent->canonical_id);
        $this->assertSame('active', $latestEvent->before_snapshot['status']);
        $this->assertSame('inactive', $latestEvent->after_snapshot['status']);
    }

    public function test_failed_audit_insert_rolls_back_canonical_offering_promotion(): void
    {
        $institution = Institution::create([
            'name' => 'Audit Rollback University',
            'slug' => 'audit-rollback-university',
            'status' => 'active',
        ]);
        $program = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Audit Rollback Program',
            'slug' => 'audit-rollback-program',
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => uniqid('audit-rollback-', true),
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

        StructuredEnrichmentPromotionEvent::creating(function (): void {
            throw new \\RuntimeException('Simulated audit persistence failure.');
        });

        try {
            app(StructuredEnrichmentPromoter::class)->promote($row);
            $this->fail('Expected the audit persistence failure to abort promotion.');
        } catch (\\RuntimeException $exception) {
            $this->assertSame('Simulated audit persistence failure.', $exception->getMessage());
        } finally {
            StructuredEnrichmentPromotionEvent::flushEventListeners();
        }

        $this->assertSame(0, ProgramOffering::where('program_id', $program->id)->count());
        $this->assertDatabaseCount('structured_enrichment_promotion_events', 0);
    }
}

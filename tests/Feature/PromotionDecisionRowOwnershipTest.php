<?php

namespace Tests\Feature;

use App\Models\IdentityResolutionDecision;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Services\InstituteImportPromoter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PromotionDecisionRowOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotion_rejects_decision_belonging_to_another_row(): void
    {
        $batch = ImportBatch::create([
            'batch_key' => uniqid('decision-owner-', true),
            'entity_type' => 'institution',
            'source_type' => 'official',
            'status' => 'validated',
        ]);
        $first = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'payload' => ['name' => 'First synthetic institution'],
            'normalized_payload' => ['name' => 'First synthetic institution'],
        ]);
        $second = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 2,
            'status' => 'candidate',
            'payload' => ['name' => 'Second synthetic institution'],
            'normalized_payload' => ['name' => 'Second synthetic institution'],
        ]);
        $decision = IdentityResolutionDecision::create([
            'import_row_id' => $first->id,
            'institution_id' => null,
            'decision' => 'approved_new',
            'actor_type' => 'user',
            'actor_id' => 1,
            'reason' => 'Synthetic approval for first row only',
            'decided_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Resolution decision does not belong to import row.');

        app(InstituteImportPromoter::class)->promote($second, $decision);
    }
}

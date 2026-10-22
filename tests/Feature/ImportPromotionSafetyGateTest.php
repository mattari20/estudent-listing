<?php

namespace Tests\Feature;

use App\Models\IdentityResolutionDecision;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Source;
use App\Services\InstituteImportPromoter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ImportPromotionSafetyGateTest extends TestCase
{
    use RefreshDatabase;

    private function candidate(string $sourceStatus = 'active', string $batchStatus = 'validated'): array
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Promotion Gate Source',
            'url' => 'https://example.test/source',
            'status' => $sourceStatus,
        ]);

        $batch = ImportBatch::create([
            'batch_key' => 'promotion-gate-' . uniqid(),
            'entity_type' => 'institution',
            'source_type' => 'official',
            'source_id' => $source->id,
            'status' => $batchStatus,
            'total_rows' => 1,
            'valid_rows' => 1,
            'candidate_rows' => 1,
            'received_at' => now(),
        ]);

        $row = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'new_candidate',
            'payload' => ['name' => 'Gate Test Institute'],
            'normalized_payload' => ['name' => 'Gate Test Institute', 'slug' => 'gate-test-institute'],
        ]);

        $decision = IdentityResolutionDecision::create([
            'import_row_id' => $row->id,
            'institution_id' => null,
            'decision' => 'approved_new',
            'actor_type' => 'user',
            'actor_id' => 1,
            'reason' => 'Explicit test approval',
            'decided_at' => now(),
        ]);

        return [$batch, $row, $decision];
    }

    public function test_promotion_fails_closed_without_active_evidence_and_preserves_row(): void
    {
        [, $row, $decision] = $this->candidate();

        try {
            app(InstituteImportPromoter::class)->promote($row, $decision);
            $this->fail('Promotion must be blocked without active evidence.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Active source evidence is required before promotion.', $exception->getMessage());
        }

        $this->assertSame('candidate', $row->fresh()->status);
        $this->assertDatabaseCount('institutions', 0);
    }

    public function test_promotion_fails_closed_for_inactive_source(): void
    {
        [, $row, $decision] = $this->candidate('inactive');

        try {
            app(InstituteImportPromoter::class)->promote($row, $decision);
            $this->fail('Promotion must be blocked for an inactive source.');
        } catch (RuntimeException $exception) {
            $this->assertSame('An active source record is required before promotion.', $exception->getMessage());
        }

        $this->assertSame('candidate', $row->fresh()->status);
        $this->assertDatabaseCount('institutions', 0);
    }

    public function test_promotion_fails_closed_for_unvalidated_batch(): void
    {
        [, $row, $decision] = $this->candidate('active', 'processing');

        try {
            app(InstituteImportPromoter::class)->promote($row, $decision);
            $this->fail('Promotion must be blocked for an unvalidated batch.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Only rows from a validated import batch can be promoted.', $exception->getMessage());
        }

        $this->assertSame('candidate', $row->fresh()->status);
        $this->assertDatabaseCount('institutions', 0);
    }

    public function test_promotion_fails_closed_without_active_evidence(): void
 {
  [$batch, $row, $decision] = $this->candidate();
  try {
   app(InstituteImportPromoter::class)->promote($row, $decision);
   $this->fail('Promotion must be blocked without active source evidence.');
  } catch (RuntimeException $exception) {
   $this->assertSame('Active source evidence is required before promotion.', $exception->getMessage());
  }
  $this->assertSame('candidate', $row->fresh()->status);
  $this->assertDatabaseCount('institutions', 0);
 }
}

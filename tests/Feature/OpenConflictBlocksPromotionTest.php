<?php

namespace Tests\Feature;

use App\Models\Assertion;
use App\Models\Evidence;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportBatch;
use App\Models\ImportConflict;
use App\Models\ImportRow;
use App\Models\Source;
use App\Models\Institution;
use App\Services\InstituteImportPromoter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OpenConflictBlocksPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_conflict_blocks_promotion_even_when_source_evidence_and_approval_exist(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Conflict Gate Source',
            'url' => 'https://example.test/conflict-source',
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => uniqid('conflict-gate-', true),
            'entity_type' => 'institution',
            'source_type' => 'official',
            'source_id' => $source->id,
            'status' => 'validated',
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
            'payload' => ['name' => 'Conflict Gate Synthetic Institute'],
            'normalized_payload' => ['name' => 'Conflict Gate Synthetic Institute', 'slug' => 'conflict-gate-synthetic-institute'],
        ]);
        $decision = IdentityResolutionDecision::create([
            'import_row_id' => $row->id,
            'institution_id' => null,
            'decision' => 'approved_new',
            'actor_type' => 'user',
            'actor_id' => 1,
            'reason' => 'Synthetic test approval',
            'decided_at' => now(),
        ]);
        $evidence = Evidence::create([
            'source_id' => $source->id,
            'reference' => 'https://example.test/conflict-institute',
            'excerpt' => 'Official source evidence for a synthetic institute.',
            'content_hash' => 'conflict-gate-' . $row->id,
            'observed_at' => now(),
            'status' => 'active',
        ]);
        Assertion::create([
            'evidence_id' => $evidence->id,
            'subject_type' => ImportRow::class,
            'subject_id' => $row->id,
            'attribute' => 'institution_identity',
            'value' => 'Conflict Gate Synthetic Institute',
            'verification_state' => 'observed',
            'confidence' => 1,
        ]);
        ImportConflict::create([
            'import_row_id' => $row->id,
            'import_batch_id' => $batch->id,
            'conflict_type' => 'identity',
            'severity' => 'blocking',
            'status' => 'open',
            'description' => 'Synthetic unresolved identity conflict.',
        ]);

        try {
            app(InstituteImportPromoter::class)->promote($row, $decision);
            $this->fail('Promotion must be blocked while an import conflict is open.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Open import conflicts must be explicitly resolved before promotion.', $exception->getMessage());
        }

        $this->assertSame('candidate', $row->fresh()->status);
        $this->assertSame(0, Institution::where('slug', 'conflict-gate-synthetic-institute')->count());
    }
}

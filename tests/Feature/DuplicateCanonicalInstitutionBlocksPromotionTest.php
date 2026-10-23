<?php

namespace Tests\Feature;

use App\Models\Assertion;
use App\Models\Evidence;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Source;
use App\Services\InstituteImportPromoter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DuplicateCanonicalInstitutionBlocksPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_new_decision_cannot_create_duplicate_canonical_institution(): void
    {
        $existing = Institution::create([
            'name' => 'Duplicate Guard University',
            'slug' => 'duplicate-guard-university',
            'status' => 'active',
        ]);
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Duplicate Guard Source',
            'url' => 'https://example.test/duplicate-source',
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => uniqid('duplicate-guard-', true),
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
            'payload' => ['name' => 'Duplicate Guard University'],
            'normalized_payload' => ['name' => 'Duplicate Guard University', 'slug' => 'duplicate-guard-university'],
        ]);
        $decision = IdentityResolutionDecision::create([
            'import_row_id' => $row->id,
            'institution_id' => null,
            'decision' => 'approved_new',
            'actor_type' => 'user',
            'actor_id' => 1,
            'reason' => 'Synthetic test decision',
            'decided_at' => now(),
        ]);
        $evidence = Evidence::create([
            'source_id' => $source->id,
            'reference' => 'https://example.test/duplicate-university',
            'excerpt' => 'Official source for a synthetic institution.',
            'content_hash' => 'duplicate-guard-' . $row->id,
            'observed_at' => now(),
            'status' => 'active',
        ]);
        Assertion::create([
            'evidence_id' => $evidence->id,
            'subject_type' => ImportRow::class,
            'subject_id' => $row->id,
            'attribute' => 'institution_identity',
            'value' => 'Duplicate Guard University',
            'verification_state' => 'observed',
            'confidence' => 1,
        ]);

        try {
            app(InstituteImportPromoter::class)->promote($row, $decision);
            $this->fail('A duplicate canonical identity must not be created.');
        } catch (RuntimeException $exception) {
            $this->assertSame('A matching canonical institution already exists; explicit approved_match identity resolution is required.', $exception->getMessage());
        }

        $this->assertSame('candidate', $row->fresh()->status);
        $this->assertSame(1, Institution::where('slug', 'duplicate-guard-university')->count());
        $this->assertSame($existing->id, Institution::where('slug', 'duplicate-guard-university')->value('id'));
    }
}

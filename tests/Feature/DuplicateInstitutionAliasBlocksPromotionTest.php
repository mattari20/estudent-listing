<?php

namespace Tests\Feature;

use App\Models\Assertion;
use App\Models\Evidence;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\InstitutionName;
use App\Models\Source;
use App\Services\InstituteImportPromoter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DuplicateInstitutionAliasBlocksPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_alias_match_blocks_approved_new_promotion(): void
    {
        $existing = Institution::create([
            'name' => 'Existing Alias University',
            'slug' => 'existing-alias-university',
            'status' => 'active',
        ]);
        InstitutionName::create([
            'institution_id' => $existing->id,
            'name' => 'Alias of New Candidate',
            'name_type' => 'short',
            'is_current' => true,
        ]);
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Alias Guard Source',
            'url' => 'https://example.test/alias-source',
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => uniqid('alias-guard-', true),
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
            'payload' => ['name' => 'Different Official Name'],
            'normalized_payload' => [
                'name' => 'Different Official Name',
                'short_name' => 'Alias of New Candidate',
                'slug' => 'different-official-name',
            ],
        ]);
        $decision = IdentityResolutionDecision::create([
            'import_row_id' => $row->id,
            'institution_id' => null,
            'decision' => 'approved_new',
            'actor_type' => 'user',
            'actor_id' => 1,
            'reason' => 'Synthetic explicit approval',
            'decided_at' => now(),
        ]);
        $evidence = Evidence::create([
            'source_id' => $source->id,
            'reference' => 'https://example.test/different-institution',
            'excerpt' => 'Official source for a synthetic institution.',
            'content_hash' => 'alias-guard-' . $row->id,
            'observed_at' => now(),
            'status' => 'active',
        ]);
        Assertion::create([
            'evidence_id' => $evidence->id,
            'subject_type' => ImportRow::class,
            'subject_id' => $row->id,
            'attribute' => 'institution_identity',
            'value' => 'Different Official Name',
            'verification_state' => 'observed',
            'confidence' => 1,
        ]);

        try {
            app(InstituteImportPromoter::class)->promote($row, $decision);
            $this->fail('A matching canonical alias must block approved-new promotion.');
        } catch (RuntimeException $exception) {
            $this->assertSame('A matching canonical institution alias already exists; explicit approved_match identity resolution is required.', $exception->getMessage());
        }

        $this->assertSame('candidate', $row->fresh()->status);
        $this->assertSame(1, Institution::count());
        $this->assertDatabaseHas('institution_names', [
            'institution_id' => $existing->id,
            'name' => 'Alias of New Candidate',
        ]);
    }
}

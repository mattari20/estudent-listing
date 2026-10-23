<?php

namespace Tests\Feature;

use App\Models\Assertion;
use App\Models\Evidence;
use App\Models\IdentityMatchCandidate;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Source;
use App\Services\InstituteImportPromoter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class InstitutionIdentityClassificationSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_new_is_blocked_when_identity_match_candidates_are_unresolved(): void
    {
        $existing = Institution::create([
            'name' => 'Existing Match University',
            'slug' => 'existing-match-university',
            'status' => 'active',
        ]);
        [$row, $decision] = $this->makeCandidate([
            'name' => 'Existing Match University Regional',
            'slug' => 'existing-match-university-regional',
        ]);

        IdentityMatchCandidate::create([
            'import_row_id' => $row->id,
            'institution_id' => $existing->id,
            'match_type' => 'possible_name_match',
            'confidence' => 0.82,
            'status' => 'candidate',
            'reason' => 'Synthetic regression fixture: unresolved possible identity match.',
            'matched_at' => now(),
        ]);

        $institutionCount = Institution::count();

        try {
            app(InstituteImportPromoter::class)->promote($row, $decision);
            $this->fail('An unresolved identity candidate must block approved_new promotion.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Unresolved identity match candidates exist. Review and resolve identity candidates before approving a new institution.',
                $exception->getMessage()
            );
        }

        $this->assertSame($institutionCount, Institution::count());
        $this->assertSame('candidate', $row->fresh()->status);
        $this->assertDatabaseHas('identity_match_candidates', [
            'import_row_id' => $row->id,
            'institution_id' => $existing->id,
            'status' => 'candidate',
        ]);
    }

    public function test_approved_new_is_blocked_for_campus_like_candidate(): void
    {
        [$row, $decision] = $this->makeCandidate([
            'name' => 'North Valley Campus',
            'official_name' => 'North Valley Campus',
            'slug' => 'north-valley-campus',
            'institution_type' => 'Public University Campus',
        ]);

        $institutionCount = Institution::count();

        try {
            app(InstituteImportPromoter::class)->promote($row, $decision);
            $this->fail('A campus-like record must not be created as a standalone institution.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'This candidate appears to describe a campus, not a standalone institution. Resolve its parent institution and import it as a campus instead of creating a new institution.',
                $exception->getMessage()
            );
        }

        $this->assertSame($institutionCount, Institution::count());
        $this->assertSame('candidate', $row->fresh()->status);
    }

    private function makeCandidate(array $payload): array
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Identity Classification Test Source',
            'url' => 'https://example.test/identity-classification',
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => 'identity-classification-' . uniqid(),
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
            'payload' => $payload,
            'normalized_payload' => $payload,
        ]);
        $decision = IdentityResolutionDecision::create([
            'import_row_id' => $row->id,
            'institution_id' => null,
            'decision' => 'approved_new',
            'actor_type' => 'user',
            'actor_id' => 1,
            'reason' => 'Synthetic regression test decision.',
            'decided_at' => now(),
        ]);
        $evidence = Evidence::create([
            'source_id' => $source->id,
            'reference' => $source->url,
            'excerpt' => 'Synthetic official-source evidence for regression coverage.',
            'content_hash' => 'identity-classification-' . $row->id,
            'observed_at' => now(),
            'status' => 'active',
        ]);
        Assertion::create([
            'evidence_id' => $evidence->id,
            'subject_type' => ImportRow::class,
            'subject_id' => $row->id,
            'attribute' => 'institution_identity',
            'value' => (string) ($payload['name'] ?? ''),
            'verification_state' => 'observed',
            'confidence' => 1,
        ]);

        return [$row, $decision];
    }
}

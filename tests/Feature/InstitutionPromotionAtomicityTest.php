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
use App\Services\ImportPromotionAuditService;
use App\Services\InstituteImportPromoter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class InstitutionPromotionAtomicityTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_failure_rolls_back_canonical_record_and_import_row_state(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Atomic Promotion Source',
            'url' => 'https://example.test/atomic-source',
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => uniqid('atomic-promotion-', true),
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
            'payload' => ['name' => 'Atomic Synthetic University'],
            'normalized_payload' => ['name' => 'Atomic Synthetic University', 'slug' => 'atomic-synthetic-university'],
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
            'reference' => 'https://example.test/atomic-university',
            'excerpt' => 'Official source evidence for a synthetic university.',
            'content_hash' => 'atomic-promotion-' . $row->id,
            'observed_at' => now(),
            'status' => 'active',
        ]);
        Assertion::create([
            'evidence_id' => $evidence->id,
            'subject_type' => ImportRow::class,
            'subject_id' => $row->id,
            'attribute' => 'institution_identity',
            'value' => 'Atomic Synthetic University',
            'verification_state' => 'observed',
            'confidence' => 1,
        ]);

        $this->mock(ImportPromotionAuditService::class)
            ->shouldReceive('record')
            ->once()
            ->andThrow(new RuntimeException('Synthetic audit persistence failure.'));

        try {
            app(InstituteImportPromoter::class)->promote($row, $decision);
            $this->fail('Promotion must fail when its audit event cannot be persisted.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic audit persistence failure.', $exception->getMessage());
        }

        $this->assertSame('candidate', $row->fresh()->status);
        $this->assertSame(0, Institution::where('slug', 'atomic-synthetic-university')->count());
        $this->assertSame(0, InstitutionName::where('name', 'Atomic Synthetic University')->count());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Assertion;
use App\Models\Evidence;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportLifecyclePublicationEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_institution_stays_private_until_explicit_publication_after_evidence_backed_promotion(): void
    {
        $operator = User::create([
            'name' => 'Lifecycle Operator',
            'email' => 'lifecycle-' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'operator',
        ]);

        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Synthetic Official Source',
            'url' => 'https://example.test/lifecycle-source',
            'status' => 'active',
        ]);

        $batch = ImportBatch::create([
            'batch_key' => 'lifecycle-' . uniqid(),
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
            'payload' => ['name' => 'Lifecycle Synthetic University'],
            'normalized_payload' => [
                'name' => 'Lifecycle Synthetic University',
                'official_name' => 'Lifecycle Synthetic University',
                'slug' => 'lifecycle-synthetic-university',
                'institution_type' => 'university',
            ],
        ]);

        $evidence = Evidence::create([
            'source_id' => $source->id,
            'reference' => 'https://example.test/lifecycle-synthetic-university',
            'excerpt' => 'Synthetic official source evidence for the lifecycle publication test.',
            'content_hash' => 'lifecycle-evidence-' . $row->id,
            'observed_at' => now(),
            'status' => 'active',
        ]);

        Assertion::create([
            'evidence_id' => $evidence->id,
            'subject_type' => ImportRow::class,
            'subject_id' => $row->id,
            'attribute' => 'institution_identity',
            'value' => 'Lifecycle Synthetic University',
            'verification_state' => 'observed',
            'confidence' => 1,
        ]);

        // Staging must not create or publish a canonical record.
        $this->assertSame(0, Institution::where('slug', 'lifecycle-synthetic-university')->count());
        $this->get('/institutes/')->assertOk()->assertDontSee('Lifecycle Synthetic University');

        // Record an explicit identity decision and promote the evidence-backed candidate.
        $this->actingAs($operator)->post(route('institutes.operations.decide', $row), [
            'decision' => 'approved_new',
            'reason' => 'Synthetic explicit approval for lifecycle test.',
        ])->assertRedirect();

        $this->actingAs($operator)->post(route('institutes.operations.promote', $row))
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('promoted', $row->status);
        $institution = Institution::findOrFail($row->canonical_subject_id);
        $this->assertSame('candidate', $institution->status);
        $this->get('/institutes/')->assertOk()->assertDontSee('Lifecycle Synthetic University');

        // Publication is a separate, explicit operator action.
        $this->actingAs($operator)->post(route('institutes.operations.publish', $row))
            ->assertRedirect();

        $row->refresh();
        $institution->refresh();
        $this->assertSame('promoted', $row->status);
        $this->assertSame('active', $institution->status);
        $this->get('/institutes/')->assertOk()->assertSee('Lifecycle Synthetic University');
        $this->get(route('institutes.show', $institution))->assertOk()->assertSee('Lifecycle Synthetic University');

        $this->assertDatabaseHas('import_promotion_events', [
            'import_row_id' => $row->id,
            'event_type' => 'institution_published',
        ]);
    }
}

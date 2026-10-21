<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportConflict;
use App\Models\Institution;
use App\Models\ImportRow;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkReviewSafeguardsTest extends TestCase
{
    use RefreshDatabase;

    private function operator(): User
    {
        return User::create([
            'name' => 'Review Operator',
            'email' => 'review-operator-' . uniqid() . '@example.test',
            'password' => 'secret',
            'role' => 'operator',
        ]);
    }

    private function institutionRow(string $name): ImportRow
    {
        $source = Source::create([
            'source_type' => 'primary',
            'name' => 'Bulk review test source',
            'url' => 'https://example.test/institutions',
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => 'bulk-review-' . uniqid(),
            'entity_type' => 'institution',
            'source_type' => 'primary',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 1,
            'valid_rows' => 1,
            'candidate_rows' => 1,
            'received_at' => now(),
        ]);

        return ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'new_candidate',
            'payload' => ['name' => $name, 'slug' => str($name)->slug()->toString()],
            'normalized_payload' => ['name' => $name, 'slug' => str($name)->slug()->toString()],
        ]);
    }

    public function test_bulk_approval_does_not_approve_institution_identity_rows(): void
    {
        $operator = $this->operator();
        $row = $this->institutionRow('Bulk Approval Safety University');

        $this->actingAs($operator)->post(route('institutes.operations.bulk-decision'), [
            'row_ids' => [$row->id],
            'decision' => 'approve',
            'reason' => 'Testing that identity rows require the dedicated decision workflow',
        ])->assertRedirect();

        $this->assertSame('candidate', $row->fresh()->status);
        $this->assertDatabaseMissing('identity_resolution_decisions', [
            'import_row_id' => $row->id,
            'decision' => 'approved_new',
        ]);
        $this->assertDatabaseMissing('import_promotion_decisions', [
            'import_row_id' => $row->id,
            'decision' => 'approved',
        ]);
    }

    public function test_bulk_rejection_records_reason_and_marks_institution_row_rejected(): void
    {
        $operator = $this->operator();
        $row = $this->institutionRow('Bulk Rejection Safety University');
        $reason = 'Rejected by automated bulk-review regression test';

        $this->actingAs($operator)->post(route('institutes.operations.bulk-decision'), [
            'row_ids' => [$row->id],
            'decision' => 'reject',
            'reason' => $reason,
        ])->assertRedirect();

        $this->assertSame('rejected', $row->fresh()->status);
        $this->assertStringContainsString($reason, (string) $row->fresh()->notes);
        $this->assertDatabaseHas('identity_resolution_decisions', [
            'import_row_id' => $row->id,
            'decision' => 'rejected',
            'reason' => $reason,
        ]);
        $this->assertDatabaseHas('import_promotion_events', [
            'import_row_id' => $row->id,
            'event_type' => 'promotion_rejected',
        ]);
    }
    public function test_bulk_rejection_skips_rows_with_open_conflicts(): void
    {
        $operator = $this->operator();
        $row = $this->institutionRow('Conflict Hold University');
        ImportConflict::create([
            'import_row_id' => $row->id,
            'import_batch_id' => $row->import_batch_id,
            'conflict_type' => 'identity_ambiguity',
            'severity' => 'blocking',
            'status' => 'open',
            'description' => 'Synthetic open conflict for regression testing',
        ]);

        $this->actingAs($operator)->post(route('institutes.operations.bulk-decision'), [
            'row_ids' => [$row->id],
            'decision' => 'reject',
            'reason' => 'This must be skipped while conflict is open',
        ])->assertRedirect();

        $this->assertSame('candidate', $row->fresh()->status);
        $this->assertDatabaseMissing('identity_resolution_decisions', [
            'import_row_id' => $row->id,
            'decision' => 'rejected',
        ]);
    }

    public function test_bulk_approval_records_decision_but_does_not_promote_enrichment_row(): void
    {
        $operator = $this->operator();
        $institution = Institution::create([
            'name' => 'Parent University',
            'slug' => 'parent-university',
            'status' => 'active',
        ]);
        $source = Source::create([
            'source_type' => 'primary',
            'name' => 'Campus review source',
            'url' => 'https://example.test/campuses',
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => 'bulk-campus-' . uniqid(),
            'entity_type' => 'campus',
            'source_type' => 'primary',
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
            'parent_subject_type' => Institution::class,
            'parent_subject_id' => $institution->id,
            'payload' => ['name' => 'Test Campus', 'slug' => 'test-campus'],
            'normalized_payload' => ['name' => 'Test Campus', 'slug' => 'test-campus'],
        ]);

        $this->actingAs($operator)->post(route('institutes.operations.bulk-decision'), [
            'row_ids' => [$row->id],
            'decision' => 'approve',
            'reason' => 'Approved in isolated regression test',
        ])->assertRedirect();

        $this->assertDatabaseHas('import_promotion_decisions', [
            'import_row_id' => $row->id,
            'decision' => 'approved',
            'reason' => 'Approved in isolated regression test',
        ]);
        $this->assertDatabaseMissing('import_promotion_events', [
            'import_row_id' => $row->id,
            'event_type' => 'promotion_succeeded',
        ]);
        $this->assertSame('candidate', $row->fresh()->status);
    }

}

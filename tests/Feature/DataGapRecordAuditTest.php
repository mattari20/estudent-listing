<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\ImportBatch;
use App\Models\ImportPromotionDecision;
use App\Models\ImportPromotionEvent;
use App\Models\ImportRow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DataGapRecordAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_audit_is_read_only_and_outputs_canonical_records(): void
    {
        Institution::query()->create([
            'name' => 'Record Audit Test Institute',
            'slug' => 'record-audit-test-institute',
            'institution_type' => 'university',
            'status' => 'candidate',
        ]);

        Artisan::call('institutes:data-gap-record-audit', ['--json' => true]);
        $output = Artisan::output();
        $report = json_decode($output, true);

        $this->assertIsArray($report);
        $this->assertSame('institutes_data_gap_record_audit', $report['check'] ?? null);
        $this->assertFalse($report['mutation_performed'] ?? true);
        $this->assertSame('Record Audit Test Institute', $report['sections']['institutions']['records'][0]['name'] ?? null);
        $this->assertStringNotContainsString('payload', json_encode($report['sections']['import_rows']['records'] ?? []));
        $this->assertSame(1, Institution::query()->where('slug', 'record-audit-test-institute')->count());
    }
    public function test_record_audit_explains_campus_import_state_without_exposing_payload_or_notes(): void
    {
        $batch = ImportBatch::query()->create([
            'batch_key' => 'campus-audit-test',
            'entity_type' => 'campus',
            'source_type' => 'test',
            'status' => 'validated',
            'total_rows' => 1,
            'valid_rows' => 1,
            'candidate_rows' => 1,
            'received_at' => now(),
            'completed_at' => now(),
        ]);

        $row = ImportRow::query()->create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'new_candidate',
            'parent_subject_type' => Institution::class,
            'parent_subject_id' => 999,
            'payload' => ['name' => 'Private test campus payload'],
            'normalized_payload' => ['name' => 'Private test campus payload'],
            'validation_errors' => ['geography_id' => ['Unknown geography']],
            'notes' => 'Private reviewer note',
        ]);

        ImportPromotionDecision::query()->create([
            'import_row_id' => $row->id,
            'decision' => 'approved',
            'actor_type' => 'user',
            'actor_id' => 1,
            'reason' => 'Test approval',
            'decided_at' => now(),
        ]);

        Artisan::call('institutes:data-gap-record-audit', ['--json' => true]);
        $report = json_decode(Artisan::output(), true);
        $record = collect($report['sections']['import_rows']['records'] ?? [])
            ->firstWhere('id', $row->id);

        $this->assertIsArray($record);
        $this->assertSame('campus', $record['entity_type']);
        $this->assertSame('candidate', $record['status']);
        $this->assertTrue($record['has_approved_promotion_decision']);
        $this->assertSame(1, $record['validation_error_count']);
        $this->assertSame(['geography_id'], $record['validation_error_fields']);
        $this->assertTrue($record['has_notes']);
        $this->assertSame('correct_validation_errors', $record['review_disposition']);
        $this->assertNotEmpty($record['review_reasons']);
        $this->assertNull($record['latest_promotion_event_type']);
        $this->assertStringNotContainsString('Private test campus payload', json_encode($record));
        $this->assertStringNotContainsString('Private reviewer note', json_encode($record));
        $this->assertStringNotContainsString('Unknown geography', json_encode($record));
    }

    public function test_record_audit_prioritizes_a_promotion_failure_over_a_missing_approval_decision(): void
    {
        $batch = ImportBatch::query()->create([
            'batch_key' => 'promotion-failure-audit-test',
            'entity_type' => 'institution',
            'source_type' => 'test',
            'status' => 'validated',
            'total_rows' => 1,
            'valid_rows' => 1,
            'candidate_rows' => 1,
            'received_at' => now(),
            'completed_at' => now(),
        ]);

        $row = ImportRow::query()->create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'new_candidate',
            'payload' => ['name' => 'Promotion Failure Audit Institute'],
            'normalized_payload' => ['name' => 'Promotion Failure Audit Institute'],
            'validation_errors' => [],
        ]);

        ImportPromotionEvent::query()->create([
            'import_row_id' => $row->id,
            'import_batch_id' => $batch->id,
            'entity_type' => 'institution',
            'event_type' => 'promotion_failed',
            'previous_status' => 'candidate',
            'new_status' => 'candidate',
            'actor_type' => 'system',
            'reason' => 'Private failure detail must not be exposed by the audit.',
            'metadata' => ['error_class' => 'RuntimeException'],
            'occurred_at' => now(),
        ]);

        Artisan::call('institutes:data-gap-record-audit', ['--json' => true]);
        $report = json_decode(Artisan::output(), true);
        $record = collect($report['sections']['import_rows']['records'] ?? [])
            ->firstWhere('id', $row->id);

        $this->assertIsArray($record);
        $this->assertSame('investigate_promotion_failure', $record['review_disposition']);
        $this->assertContains(
            'A promotion failure event exists; inspect its private details before retrying.',
            $record['review_reasons']
        );
        $this->assertSame('RuntimeException', $record['latest_promotion_error_class']);
        $this->assertSame('other_runtime_exception', $record['latest_promotion_error_category']);
        $this->assertStringNotContainsString('Private failure detail', json_encode($record));
    }


    public function test_summary_mode_reports_disposition_counts_without_row_level_details(): void
    {
        $batch = ImportBatch::query()->create([
            'batch_key' => 'summary-audit-test',
            'entity_type' => 'institution',
            'source_type' => 'test',
            'status' => 'validated',
            'total_rows' => 1,
            'valid_rows' => 1,
            'candidate_rows' => 1,
            'received_at' => now(),
            'completed_at' => now(),
        ]);

        ImportRow::query()->create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'new_candidate',
            'payload' => ['name' => 'Private summary test payload'],
            'normalized_payload' => ['name' => 'Private summary test payload'],
            'validation_errors' => [],
        ]);

        Artisan::call('institutes:data-gap-record-audit', [
            '--json' => true,
            '--summary' => true,
        ]);
        $output = Artisan::output();
        $report = json_decode($output, true);

        $this->assertIsArray($report);
        $this->assertTrue($report['summary_only'] ?? false);
        $this->assertFalse($report['mutation_performed'] ?? true);
        $this->assertSame(1, $report['sections']['import_rows']['total'] ?? null);
        $this->assertSame(
            1,
            $report['sections']['import_rows']['by_review_disposition']['review_promotion_decision'] ?? null
        );
        $this->assertArrayNotHasKey('records', $report['sections']['import_rows'] ?? []);
        $this->assertStringNotContainsString('Private summary test payload', $output);
        $this->assertStringNotContainsString('"records"', $output);
    }

    public function test_record_audit_classifies_known_institution_promotion_failures_without_exposing_reasons(): void
    {
        $batch = ImportBatch::query()->create([
            'batch_key' => 'classified-promotion-failures',
            'entity_type' => 'institution',
            'source_type' => 'test',
            'status' => 'validated',
            'total_rows' => 3,
            'valid_rows' => 3,
            'candidate_rows' => 3,
            'received_at' => now(),
            'completed_at' => now(),
        ]);

        $cases = [
            ['id' => 'collision', 'reason' => 'A matching canonical institution already exists; explicit approved_match identity resolution is required.', 'category' => 'canonical_identity_collision'],
            ['id' => 'evidence', 'reason' => 'Active source evidence is required before promotion.', 'category' => 'missing_active_source_evidence'],
            ['id' => 'conflict', 'reason' => 'Open import conflicts must be explicitly resolved before promotion.', 'category' => 'unresolved_import_conflict'],
        ];

        $rowIds = [];
        foreach ($cases as $index => $case) {
            $row = ImportRow::query()->create([
                'import_batch_id' => $batch->id,
                'row_number' => $index + 1,
                'status' => 'candidate',
                'match_state' => 'new_candidate',
                'payload' => ['name' => 'Failure category '.$case['id']],
                'normalized_payload' => ['name' => 'Failure category '.$case['id']],
                'validation_errors' => [],
            ]);
            $rowIds[$case['id']] = $row->id;

            ImportPromotionEvent::query()->create([
                'import_row_id' => $row->id,
                'import_batch_id' => $batch->id,
                'entity_type' => 'institution',
                'event_type' => 'promotion_failed',
                'previous_status' => 'candidate',
                'new_status' => 'candidate',
                'actor_type' => 'system',
                'reason' => $case['reason'],
                'metadata' => ['error_class' => 'RuntimeException'],
                'occurred_at' => now()->addSeconds($index),
            ]);
        }

        Artisan::call('institutes:data-gap-record-audit', ['--json' => true]);
        $report = json_decode(Artisan::output(), true);
        $records = collect($report['sections']['import_rows']['records'] ?? [])->keyBy('id');

        foreach ($cases as $case) {
            $record = $records->get($rowIds[$case['id']]);
            $this->assertIsArray($record);
            $this->assertSame($case['category'], $record['latest_promotion_error_category']);
            $this->assertStringNotContainsString($case['reason'], json_encode($record));
        }
    }


}

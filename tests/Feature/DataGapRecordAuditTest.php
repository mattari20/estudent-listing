<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\ImportBatch;
use App\Models\ImportPromotionDecision;
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

}

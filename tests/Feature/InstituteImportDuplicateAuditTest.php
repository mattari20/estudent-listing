<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Source;
use App\Models\Institution;
use App\Services\InstituteImportDuplicateAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteImportDuplicateAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_cross_batch_audit_flags_duplicates_and_missing_names_without_mutating_rows(): void
    {
        $source = Source::query()->create([
            'source_type' => 'test',
            'name' => 'Audit source',
            'url' => 'https://example.test/audit',
            'status' => 'active',
        ]);
        $batchOne = ImportBatch::query()->create([
            'batch_key' => 'audit-batch-one', 'entity_type' => 'institution', 'source_type' => 'test',
            'source_id' => $source->id, 'status' => 'validated', 'total_rows' => 1,
            'valid_rows' => 1, 'invalid_rows' => 0, 'candidate_rows' => 1,
        ]);
        $batchTwo = ImportBatch::query()->create([
            'batch_key' => 'audit-batch-two', 'entity_type' => 'institution', 'source_type' => 'test',
            'source_id' => $source->id, 'status' => 'validated', 'total_rows' => 2,
            'valid_rows' => 1, 'invalid_rows' => 1, 'candidate_rows' => 1,
        ]);
        $first = ImportRow::query()->create([
            'import_batch_id' => $batchOne->id, 'row_number' => 2, 'status' => 'candidate',
            'match_state' => 'unresolved', 'payload' => ['name' => 'Example University'],
            'normalized_payload' => ['name' => 'Example University'],
        ]);
        $duplicate = ImportRow::query()->create([
            'import_batch_id' => $batchTwo->id, 'row_number' => 2, 'status' => 'candidate',
            'match_state' => 'unresolved', 'payload' => ['name' => 'Example University'],
            'normalized_payload' => ['name' => 'Example University'],
        ]);
        $incomplete = ImportRow::query()->create([
            'import_batch_id' => $batchTwo->id, 'row_number' => 3, 'status' => 'invalid',
            'match_state' => 'unresolved', 'payload' => ['name' => ''],
            'normalized_payload' => ['name' => ''],
        ]);

        $report = app(InstituteImportDuplicateAuditService::class)->build();

        $this->assertFalse($report['mutation_performed']);
        $this->assertSame(3, $report['summary']['rows_scanned']);
        $this->assertSame(1, $report['summary']['duplicate_import_rows']);
        $this->assertSame(1, $report['summary']['incomplete_rows']);
        $this->assertSame('candidate', $first->fresh()->status);
        $this->assertSame('candidate', $duplicate->fresh()->status);
        $this->assertSame('invalid', $incomplete->fresh()->status);
        $duplicateReport = collect($report['rows'])->firstWhere('row_id', $duplicate->id);
        $this->assertContains('duplicate_import_row', $duplicateReport['issues']);
        $this->assertSame($first->id, $duplicateReport['duplicate_of']['row_id']);
    }

    public function test_cross_batch_audit_flags_rows_matching_existing_canonical_institutions(): void
    {
        $source = Source::query()->create([
            'source_type' => 'test', 'name' => 'Canonical audit source',
            'url' => 'https://example.test/canonical', 'status' => 'active',
        ]);
        $batch = ImportBatch::query()->create([
            'batch_key' => 'audit-canonical-batch', 'entity_type' => 'institution', 'source_type' => 'test',
            'source_id' => $source->id, 'status' => 'validated', 'total_rows' => 1,
            'valid_rows' => 1, 'invalid_rows' => 0, 'candidate_rows' => 1,
        ]);
        $institution = Institution::query()->create([
            'name' => 'Canonical University', 'slug' => 'canonical-university', 'status' => 'active',
        ]);
        $row = ImportRow::query()->create([
            'import_batch_id' => $batch->id, 'row_number' => 2, 'status' => 'candidate',
            'match_state' => 'unresolved', 'payload' => ['name' => 'Canonical University'],
            'normalized_payload' => ['name' => 'Canonical University'],
        ]);

        $report = app(InstituteImportDuplicateAuditService::class)->build();
        $rowReport = collect($report['rows'])->firstWhere('row_id', $row->id);

        $this->assertSame($institution->id, $rowReport['canonical_match']['id']);
        $this->assertContains('matches_existing_canonical_institution', $rowReport['issues']);
    }

    public function test_audit_flags_invalid_rows_even_when_the_institution_name_is_present(): void
    {
        $source = Source::query()->create([
            'source_type' => 'test', 'name' => 'Validation audit source',
            'url' => 'https://example.test/validation', 'status' => 'active',
        ]);
        $batch = ImportBatch::query()->create([
            'batch_key' => 'audit-validation-batch', 'entity_type' => 'institution', 'source_type' => 'test',
            'source_id' => $source->id, 'status' => 'validated', 'total_rows' => 1,
            'valid_rows' => 0, 'invalid_rows' => 1, 'candidate_rows' => 0,
        ]);
        $row = ImportRow::query()->create([
            'import_batch_id' => $batch->id, 'row_number' => 2, 'status' => 'invalid',
            'match_state' => 'unresolved',
            'payload' => ['name' => 'Incomplete Example University'],
            'normalized_payload' => ['name' => 'Incomplete Example University'],
            'validation_errors' => ['slug' => ['The slug field is required.']],
        ]);

        $report = app(InstituteImportDuplicateAuditService::class)->build();
        $rowReport = collect($report['rows'])->firstWhere('row_id', $row->id);

        $this->assertContains('import_row_invalid', $rowReport['issues']);
        $this->assertContains('validation_errors_present', $rowReport['issues']);
        $this->assertSame(['slug' => ['The slug field is required.']], $rowReport['validation_errors']);
        $this->assertSame(1, $report['summary']['incomplete_rows']);
        $this->assertSame('invalid', $row->fresh()->status);
    }
}

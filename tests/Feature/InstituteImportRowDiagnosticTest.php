<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Source;
use App\Services\InstituteImportRowDiagnosticService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteImportRowDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    public function test_row_diagnostics_identify_candidate_blockers_without_mutating_data(): void
    {
        $source = Source::query()->create([
            'source_type' => 'test',
            'name' => 'Diagnostic test source',
            'url' => 'https://example.test/institutions',
            'status' => 'active',
        ]);
        $batch = ImportBatch::query()->create([
            'batch_key' => 'diagnostic-test-batch',
            'entity_type' => 'institution',
            'source_type' => 'test',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 1,
            'valid_rows' => 1,
            'invalid_rows' => 0,
            'candidate_rows' => 1,
        ]);
        $row = ImportRow::query()->create([
            'import_batch_id' => $batch->id,
            'row_number' => 2,
            'status' => 'candidate',
            'match_state' => 'unresolved',
            'payload' => ['name' => 'Example Test Institution'],
            'normalized_payload' => ['name' => 'Example Test Institution'],
        ]);

        $beforeStatus = $row->fresh()->status;
        $report = app(InstituteImportRowDiagnosticService::class)->build($batch->fresh());

        $this->assertFalse($report['mutation_performed']);
        $this->assertSame(1, $report['summary']['total']);
        $this->assertSame(1, $report['summary']['blocked']);
        $this->assertSame($beforeStatus, $row->fresh()->status);
        $this->assertContains('identity_decision_required', $report['rows'][0]['blockers']);
        $this->assertContains('active_source_evidence_required', $report['rows'][0]['blockers']);
    }

    public function test_row_diagnostics_report_promoted_but_unpublished_institution(): void
    {
        $source = Source::query()->create([
            'source_type' => 'test',
            'name' => 'Diagnostic test source',
            'url' => 'https://example.test/institutions',
            'status' => 'active',
        ]);
        $batch = ImportBatch::query()->create([
            'batch_key' => 'diagnostic-publication-batch',
            'entity_type' => 'institution',
            'source_type' => 'test',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 1,
            'valid_rows' => 1,
            'invalid_rows' => 0,
            'candidate_rows' => 0,
        ]);
        $institution = \App\Models\Institution::query()->create([
            'name' => 'Example Candidate Institution',
            'slug' => 'example-candidate-institution',
            'status' => 'candidate',
        ]);
        $row = ImportRow::query()->create([
            'import_batch_id' => $batch->id,
            'row_number' => 2,
            'status' => 'promoted',
            'match_state' => 'new_canonical',
            'canonical_subject_type' => \App\Models\Institution::class,
            'canonical_subject_id' => $institution->id,
            'payload' => ['name' => 'Example Candidate Institution'],
            'normalized_payload' => ['name' => 'Example Candidate Institution'],
        ]);

        $report = app(InstituteImportRowDiagnosticService::class)->build($batch->fresh());

        $this->assertSame(1, $report['summary']['promoted']);
        $this->assertContains('publication_required', $report['rows'][0]['blockers']);
        $this->assertSame('candidate', $report['rows'][0]['canonical_status']);
        $this->assertSame('promoted', $row->fresh()->status);
    }
}

<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Program;
use App\Models\Institution;
use App\Services\EnrichmentPromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class IntakeImportParentTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admission_intake_import_rejects_program_as_parent(): void
    {
        $institution = Institution::create([
            'name' => 'Intake Parent University',
            'slug' => 'intake-parent-university',
            'status' => 'active',
        ]);
        $program = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Intake Parent Program',
            'slug' => 'intake-parent-program',
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => uniqid('intake-parent-', true),
            'entity_type' => 'admission_intake',
            'source_type' => 'primary',
            'status' => 'validated',
        ]);
        $row = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'payload' => ['label' => 'Synthetic intake'],
            'normalized_payload' => ['label' => 'Synthetic intake'],
            'parent_subject_type' => Program::class,
            'parent_subject_id' => $program->id,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Admission intake imports require a Program Offering parent.');

        app(EnrichmentPromotionService::class)->validateParent($row);
    }
}

<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Services\EnrichmentPromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntakeImportValidParentTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admission_intake_import_accepts_program_offering_parent(): void
    {
        $institution = Institution::create([
            'name' => 'Valid Intake Parent University',
            'slug' => 'valid-intake-parent-university',
            'status' => 'active',
        ]);
        $program = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Valid Intake Parent Program',
            'slug' => 'valid-intake-parent-program',
            'status' => 'active',
        ]);
        $offering = ProgramOffering::create([
            'program_id' => $program->id,
            'campus_id' => null,
            'delivery_mode' => 'online',
            'status' => 'active',
            'availability' => 'unknown',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => uniqid('intake-parent-valid-', true),
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
            'parent_subject_type' => ProgramOffering::class,
            'parent_subject_id' => $offering->id,
        ]);

        app(EnrichmentPromotionService::class)->validateParent($row);

        $this->assertSame(ProgramOffering::class, $row->parent_subject_type);
        $this->assertSame($offering->id, $row->parent_subject_id);
    }
}

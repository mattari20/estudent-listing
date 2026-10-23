<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Program;
use App\Services\EnrichmentPromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CampusImportParentTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_campus_import_rejects_program_as_parent(): void
    {
        $institution = Institution::create([
            'name' => 'Parent Type University',
            'slug' => 'parent-type-university',
            'status' => 'active',
        ]);
        $program = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Parent Type Program',
            'slug' => 'parent-type-program',
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => uniqid('campus-parent-', true),
            'entity_type' => 'campus',
            'source_type' => 'primary',
            'status' => 'validated',
        ]);
        $row = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'payload' => ['name' => 'Synthetic Campus'],
            'normalized_payload' => ['name' => 'Synthetic Campus'],
            'parent_subject_type' => Program::class,
            'parent_subject_id' => $program->id,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Campus/program imports require an Institution parent.');

        app(EnrichmentPromotionService::class)->validateParent($row);
    }
}

<?php
namespace Tests\Feature;

use App\Models\Geography;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Program;
use App\Services\StructuredEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructuredEnrichmentTest extends TestCase
{
    use RefreshDatabase;

    private function row(string $type, array $payload, object $parent): ImportRow
    {
        $batch = ImportBatch::create([
            'batch_key' => uniqid('enrich-'),
            'entity_type' => $type,
            'source_type' => 'primary',
            'status' => 'validated',
        ]);

        return ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'resolved',
            'parent_subject_type' => $parent::class,
            'parent_subject_id' => $parent->id,
            'payload' => $payload,
            'normalized_payload' => $payload,
        ]);
    }

    public function test_program_enrichment_requires_canonical_institution(): void
    {
        $institution = Institution::create([
            'name' => 'Example University',
            'slug' => 'example-university',
            'status' => 'active',
        ]);
        $row = $this->row('program', [
            'name' => 'BS Computer Science',
            'slug' => 'bs-computer-science',
        ], $institution);

        $program = app(StructuredEnrichmentService::class)->promote($row);

        $this->assertInstanceOf(Program::class, $program);
        $this->assertSame($institution->id, $program->institution_id);
    }

    public function test_enrichment_is_not_allowed_for_unresolved_parent(): void
    {
        $batch = ImportBatch::create([
            'batch_key' => uniqid('enrich-'),
            'entity_type' => 'program',
            'source_type' => 'primary',
            'status' => 'validated',
        ]);
        $row = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'payload' => ['name' => 'Test'],
            'normalized_payload' => ['name' => 'Test'],
        ]);

        $this->expectException(\RuntimeException::class);
        app(StructuredEnrichmentService::class)->promote($row);
    }

    public function test_program_offering_rejects_campus_from_another_institution(): void
    {
        $owner = Institution::create([
            'name' => 'Owner University',
            'slug' => 'owner-university',
            'status' => 'active',
        ]);
        $other = Institution::create([
            'name' => 'Other University',
            'slug' => 'other-university',
            'status' => 'active',
        ]);
        $program = Program::create([
            'institution_id' => $owner->id,
            'name' => 'BS Computer Science',
            'slug' => 'bs-computer-science',
            'status' => 'active',
        ]);
        $campus = \App\Models\Campus::create([
            'institution_id' => $other->id,
            'name' => 'Other Campus',
            'slug' => 'other-campus',
            'status' => 'active',
        ]);
        $row = $this->row('program_offering', [
            'campus_id' => $campus->id,
            'delivery_mode' => 'on_campus',
        ], $program);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The selected campus does not belong to the institution that owns this program.');
        app(StructuredEnrichmentService::class)->promote($row);
    }

    public function test_campus_promotion_rejects_unknown_geography(): void
    {
        $institution = Institution::create([
            'name' => 'Geography Test University',
            'slug' => 'geography-test-university',
            'status' => 'active',
        ]);
        $row = $this->row('campus', [
            'name' => 'Test Campus',
            'slug' => 'test-campus',
            'campus_type' => 'main',
            'geography_id' => 999999,
        ], $institution);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The selected geography does not exist or is not active.');
        app(StructuredEnrichmentService::class)->promote($row);
    }

    public function test_campus_promotion_accepts_active_geography(): void
    {
        $institution = Institution::create([
            'name' => 'Geography Valid University',
            'slug' => 'geography-valid-university',
            'status' => 'active',
        ]);
        $geography = Geography::create([
            'name' => 'Punjab',
            'slug' => 'punjab',
            'type' => 'province',
            'status' => 'active',
        ]);
        $row = $this->row('campus', [
            'name' => 'Valid Campus',
            'slug' => 'valid-campus',
            'campus_type' => 'main',
            'geography_id' => $geography->id,
        ], $institution);

        $campus = app(StructuredEnrichmentService::class)->promote($row);

        $this->assertSame($geography->id, $campus->geography_id);
        $this->assertSame($institution->id, $campus->institution_id);
        $this->assertSame('Valid Campus', $campus->name);
        $this->assertSame('active', $campus->status);
        $this->assertDatabaseHas('campuses', [
            'id' => $campus->id,
            'institution_id' => $institution->id,
            'geography_id' => $geography->id,
            'slug' => 'valid-campus',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('import_rows', [
            'id' => $row->id,
            'status' => 'promoted',
            'canonical_subject_type' => \App\Models\Campus::class,
            'canonical_subject_id' => $campus->id,
        ]);
    }

    public function test_campus_promotion_does_not_overwrite_an_existing_canonical_campus(): void
    {
        $institution = Institution::create([
            'name' => 'Duplicate Campus University',
            'slug' => 'duplicate-campus-university',
            'status' => 'active',
        ]);
        $existing = \App\Models\Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Existing Main Campus',
            'slug' => 'main-campus',
            'status' => 'active',
        ]);
        $row = $this->row('campus', [
            'name' => 'Different Name From Import',
            'slug' => 'main-campus',
            'status' => 'active',
        ], $institution);

        $this->expectException(\\RuntimeException::class);
        $this->expectExceptionMessage('This campus already exists in canonical data');

        try {
            app(StructuredEnrichmentService::class)->promote($row);
        } finally {
            $this->assertDatabaseCount('campuses', 1);
            $this->assertDatabaseHas('campuses', [
                'id' => $existing->id,
                'name' => 'Existing Main Campus',
                'slug' => 'main-campus',
            ]);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use App\Services\ImportSchemaProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InstitutionCleanImportFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_clean_candidate_csv_stages_nine_rows_without_creating_or_promoting_institutions(): void
    {
        $operator = User::create([
            'name' => 'Import Operator',
            'email' => 'clean-import-operator@example.test',
            'password' => 'secret',
            'role' => 'operator',
        ]);

        $profile = app(ImportSchemaProfileService::class)->institution();
        $csvPath = base_path('data/imports/2026-10-review-pack/institution-candidates-clean-v1.0.csv');
        $this->assertFileExists($csvPath);
        $csv = file_get_contents($csvPath);
        $this->assertNotFalse($csv);

        $institutionCountBefore = Institution::count();

        $response = $this->actingAs($operator)->post('/institutes/operations/import', [
            'source_name' => 'Deduplicated institution review pack test',
            'source_type' => 'primary',
            'source_url' => 'https://www.hec.gov.pk/english/universities/Pages/DAIs/HEC-recognized-Campuses.aspx',
            'entity_type' => 'institution',
            'schema_version' => $profile->version,
            'csv' => UploadedFile::fake()->createWithContent('institution-candidates-clean-v1.0.csv', $csv),
        ]);

        $response->assertOk();
        $batchId = $response->viewData('batch')->id;
        $rows = DB::table('import_rows')->where('import_batch_id', $batchId)->get();

        $this->assertCount(9, $rows, 'The single cleaned CSV should stage exactly nine candidate rows.');
        $this->assertSame($institutionCountBefore, Institution::count(), 'Staging must not create canonical institution records.');
        $this->assertSame(0, $rows->where('status', 'promoted')->count(), 'Import staging must not promote records.');
        $this->assertSame(0, $rows->where('status', 'approved')->count(), 'Import staging must not approve records.');
        $this->assertSame(9, $rows->whereIn('status', ['candidate', 'duplicate', 'invalid', 'validated'])->count(),
            'Every imported row must remain in a reviewable non-promoted state.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use App\Services\ImportSchemaProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InstitutionImportIdentityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_resolves_existing_institution_without_creating_duplicate(): void
    {
        $operator = User::create([
            'name' => 'Import Operator',
            'email' => 'import-operator@example.test',
            'password' => 'secret',
            'role' => 'operator',
        ]);

        $existing = Institution::create([
            'name' => 'Example University',
            'official_name' => 'Example University Pakistan',
            'short_name' => 'EUP',
            'slug' => 'example-university',
            'status' => 'active',
        ]);
        $institutionCountBeforeUpload = Institution::count();

        $profile = app(ImportSchemaProfileService::class)->institution();
        $csv = implode("\n", [
            'name,official_name,short_name,slug,institution_type,status',
            'Example University,Example University Pakistan,EUP,example-university,,',
            '',
        ]);

        $response = $this->actingAs($operator)->post('/institutes/operations/import', [
            'source_name' => 'Institution identity integration test',
            'source_type' => 'primary',
            'source_url' => 'https://example.test/institutions',
            'entity_type' => 'institution',
            'schema_version' => $profile->version,
            'csv' => UploadedFile::fake()->createWithContent('institution-candidates.csv', $csv),
        ]);

        $response->assertOk();
        $batchId = $response->viewData('batch')->id;
        $rowId = DB::table('import_rows')->where('import_batch_id', $batchId)->value('id');

        // Migrations seed baseline institutions, so compare with the pre-upload count.
        $this->assertSame($institutionCountBeforeUpload, Institution::count());
        $this->assertDatabaseHas('import_rows', [
            'id' => $rowId,
            'import_batch_id' => $batchId,
            'row_number' => 2,
            'status' => 'candidate',
            'match_state' => 'matched',
        ]);
        $this->assertDatabaseHas('identity_match_candidates', [
            'import_row_id' => $rowId,
            'institution_id' => $existing->id,
            'match_type' => 'exact_slug',
            'status' => 'candidate',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EnrichmentImportDuplicateCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_csv_rows_are_counted_separately_from_candidates(): void
    {
        $operator = User::create([
            'name' => 'Duplicate Count Operator',
            'email' => 'duplicate-count-' . uniqid() . '@example.test',
            'password' => 'secret',
            'role' => 'operator',
        ]);
        $institution = Institution::create([
            'name' => 'Duplicate Count University',
            'slug' => 'duplicate-count-university',
            'status' => 'active',
        ]);
        $csv = "name,slug\nMain Campus,main-campus\nMain Campus,main-campus\n";

        $response = $this->actingAs($operator)->post(route('institutes.enrichment-import.store'), [
            'source_name' => 'Duplicate count synthetic source',
            'source_type' => 'official',
            'source_url' => 'https://example.test/duplicate-count',
            'entity_type' => 'campus',
            'schema_version' => '1.0',
            'parent_institution_id' => $institution->id,
            'csv' => UploadedFile::fake()->createWithContent('campuses.csv', $csv),
        ]);

        $response->assertOk();
        $batch = $response->viewData('batch');
        $fresh = ImportBatch::findOrFail($batch->id);

        $this->assertSame(2, $fresh->total_rows);
        $this->assertSame(1, $fresh->candidate_rows);
        $this->assertSame(1, $fresh->duplicate_rows);
    }
}

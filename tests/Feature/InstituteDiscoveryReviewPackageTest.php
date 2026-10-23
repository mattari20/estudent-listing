<?php

namespace Tests\Feature;

use App\Models\Evidence;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Source;
use App\Models\SourceSnapshot;
use App\Services\InstituteDiscoveryReviewPackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstituteDiscoveryReviewPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_contains_candidate_provenance_and_never_approves_or_promotes(): void
    {
        Storage::fake('local');
        $institution = Institution::create([
            'name' => 'Test University',
            'slug' => 'test-university',
            'status' => 'active',
        ]);
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Test official source',
            'url' => 'https://example.edu/programs',
            'authority_scope' => 'official programme listing',
            'status' => 'active',
        ]);
        $snapshot = SourceSnapshot::create([
            'source_id' => $source->id,
            'reference' => $source->url,
            'content_hash' => hash('sha256', '<p>Computer Science</p>'),
            'content_length' => strlen('<p>Computer Science</p>'),
            'content_type' => 'text/html',
            'content' => '<p>Computer Science</p>',
            'captured_at' => now(),
            'status' => 'captured',
            'metadata' => [],
        ]);
        $evidence = Evidence::create([
            'source_id' => $source->id,
            'snapshot_id' => $snapshot->id,
            'reference' => $source->url,
            'excerpt' => 'Computer Science',
            'content_hash' => $snapshot->content_hash,
            'observed_at' => now(),
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => 'agent-test-package',
            'entity_type' => 'program',
            'source_type' => 'automated_discovery',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 1,
            'valid_rows' => 1,
            'invalid_rows' => 0,
            'candidate_rows' => 1,
            'received_at' => now(),
            'completed_at' => now(),
        ]);
        ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'unresolved',
            'parent_subject_type' => Institution::class,
            'parent_subject_id' => $institution->id,
            'payload' => [
                'name' => 'Computer Science',
                'slug' => 'computer-science',
                '_provenance' => [
                    'method' => 'deterministic',
                    'confidence' => 0.95,
                    'snapshot_id' => $snapshot->id,
                    'evidence_id' => $evidence->id,
                ],
            ],
            'normalized_payload' => ['name' => 'Computer Science', 'slug' => 'computer-science'],
            'validation_errors' => null,
        ]);

        $service = app(InstituteDiscoveryReviewPackageService::class);
        $result = $service->create($batch->fresh());

        $this->assertSame('created', $result['status']);
        $this->assertSame(1, $result['candidate_count']);
        Storage::disk('local')->assertExists($result['csv_path']);
        Storage::disk('local')->assertExists($result['manifest_path']);

        $manifest = json_decode(Storage::disk('local')->get($result['manifest_path']), true);
        $this->assertSame($snapshot->id, $manifest['snapshot_id']);
        $this->assertFalse($manifest['canonical_mutations_performed']);
        $this->assertFalse($manifest['automatic_approval_performed']);
        $this->assertStringContainsString('Computer Science', Storage::disk('local')->get($result['csv_path']));

        $again = $service->create($batch->fresh());
        $this->assertSame('reused', $again['status']);
        $this->assertDatabaseHas('import_rows', ['id' => ImportRow::query()->value('id'), 'status' => 'candidate']);
        $this->assertSame(1, Institution::where('slug', 'test-university')->count());
    }
}

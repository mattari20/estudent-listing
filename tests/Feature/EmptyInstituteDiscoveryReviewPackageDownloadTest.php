<?php

namespace Tests\Feature;

use App\Http\Controllers\InstituteDiscoveryReviewPackageController;
use App\Models\ImportBatch;
use App\Models\Source;
use App\Models\SourceSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmptyInstituteDiscoveryReviewPackageDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_discovery_batch_can_download_its_private_review_manifest_using_snapshot_note(): void
    {
        Storage::fake('local');

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
            'content_hash' => hash('sha256', '<p>No programs</p>'),
            'content_length' => strlen('<p>No programs</p>'),
            'content_type' => 'text/html',
            'content' => '<p>No programs</p>',
            'captured_at' => now(),
            'status' => 'captured',
            'metadata' => [],
        ]);
        $batch = ImportBatch::create([
            'batch_key' => 'agent-empty-package',
            'entity_type' => 'program',
            'source_type' => 'automated_discovery',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'candidate_rows' => 0,
            'received_at' => now(),
            'completed_at' => now(),
            'notes' => 'No candidates extracted from source snapshot #'.$snapshot->id.'. This is a recorded no-change outcome.',
        ]);

        $path = 'institute-discovery-review-packages/snapshot-'.$snapshot->id.'/manifest.json';
        Storage::disk('local')->put($path, json_encode([
            'status' => 'no_candidates',
            'batch_id' => $batch->id,
            'snapshot_id' => $snapshot->id,
            'candidate_count' => 0,
        ]));

        $response = app(InstituteDiscoveryReviewPackageController::class)
            ->download(Request::create('/'), $batch, 'manifest');

        $this->assertSame('private, no-store, max-age=0', $response->headers->get('Cache-Control'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('manifest.json', $response->getFile()->getFilename());
    }
}

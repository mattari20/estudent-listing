<?php

namespace Tests\Feature;

use App\Models\Evidence;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Source;
use App\Models\SourceSnapshot;
use App\Services\ImportDuplicateDetectorService;
use App\Services\InstituteDiscoveryAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DiscoveryInstitutionIdentityIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_changed_non_substantive_source_snapshots_do_not_create_repeated_candidate_identities(): void
    {
        Storage::fake('local');

        $url = config('institute_agent.profiles.gcu-lahore-institution-identity.source.url');
        $firstHtml = '<html><head><title>Government College University, Lahore</title></head><body><h1>Government College University, Lahore</h1><p>Recognized university.</p></body></html>';
        $secondHtml = '<html><head><title>Government College University, Lahore</title></head><body><h1>Government College University, Lahore</h1><p>Recognized university.</p><footer>Last updated October 29.</footer></body></html>';
        $thirdHtml = '<html><head><title>Government College University, Lahore</title></head><body><h1>Government College University, Lahore</h1><p>Recognized university.</p><div class="analytics">page view 3</div></body></html>';

        Http::fake([
            $url => Http::sequence()
                ->push($firstHtml, 200, ['Content-Type' => 'text/html'])
                ->push($secondHtml, 200, ['Content-Type' => 'text/html'])
                ->push($thirdHtml, 200, ['Content-Type' => 'text/html']),
        ]);

        $service = app(InstituteDiscoveryAgentService::class);
        $first = $service->run('gcu-lahore-institution-identity');
        $second = $service->run('gcu-lahore-institution-identity');
        $third = $service->run('gcu-lahore-institution-identity');

        $this->assertSame(1, $first['candidate_rows']);
        $this->assertSame(1, $second['duplicate_rows']);
        $this->assertSame(1, $third['duplicate_rows']);
        $this->assertSame(0, $second['candidate_rows']);
        $this->assertSame(0, $third['candidate_rows']);

        $this->assertSame(3, ImportBatch::where('source_id', $first['source_id'])
            ->where('source_type', 'automated_discovery')->count());
        $this->assertSame(3, SourceSnapshot::where('source_id', $first['source_id'])->count());
        $this->assertSame(3, Evidence::where('source_id', $first['source_id'])->count());
        $this->assertSame(1, ImportRow::where('status', 'candidate')
            ->whereHas('batch', fn ($query) => $query->where('source_id', $first['source_id']))->count());
        $this->assertSame(2, ImportRow::where('status', 'duplicate')
            ->whereHas('batch', fn ($query) => $query->where('source_id', $first['source_id']))->count());

        $this->assertNotSame($first['snapshot_id'], $second['snapshot_id']);
        $this->assertNotSame($second['snapshot_id'], $third['snapshot_id']);
        $this->assertDatabaseHas('import_batches', ['id' => $first['batch_id']]);
        $this->assertDatabaseHas('import_rows', ['id' => ImportRow::where('import_batch_id', $first['batch_id'])->value('id'), 'status' => 'candidate']);
        $this->assertFalse((bool) data_get(
            json_decode(Storage::disk('local')->get($third['review_package']['manifest_path']), true),
            'canonical_mutations_performed'
        ));
    }

    public function test_legacy_slug_variant_is_detected_from_normalized_institution_name(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'HEC GCU identity',
            'url' => 'https://example.gov.pk/gcu',
            'authority_scope' => 'official university identity',
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => 'agent-gcu-legacy',
            'entity_type' => 'institution',
            'source_type' => 'automated_discovery',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 1,
            'candidate_rows' => 1,
            'received_at' => now(),
        ]);
        ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'unresolved',
            'payload' => [
                'name' => 'Government College University, Lahore',
                'official_name' => 'Government College University Lahore',
                'slug' => 'gcu-lahore-legacy',
            ],
            'normalized_payload' => [
                'name' => 'Government College University, Lahore',
                'official_name' => 'Government College University Lahore',
                'slug' => 'gcu-lahore-legacy',
            ],
        ]);

        $duplicate = app(ImportDuplicateDetectorService::class)->findInstitution(
            'government-college-university-lahore',
            null,
            [],
            [
                'name' => 'Government College University Lahore',
                'official_name' => 'Government College University, Lahore',
                'slug' => 'government-college-university-lahore',
            ]
        );

        $this->assertSame('import', $duplicate['kind']);
        $this->assertSame('Import Row', $duplicate['label']);
        $this->assertSame('The same normalized institution identity was already staged. Review the existing import row instead of creating another copy.', $duplicate['message']);
    }

    public function test_shared_generic_name_does_not_match_a_distinct_official_institution_identity(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'HEC GCUF identity',
            'url' => 'https://example.gov.pk/gcuf',
            'authority_scope' => 'official university identity',
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => 'agent-gcuf-legacy',
            'entity_type' => 'institution',
            'source_type' => 'automated_discovery',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 1,
            'candidate_rows' => 1,
            'received_at' => now(),
        ]);
        ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'unresolved',
            'payload' => [
                'name' => 'Government College University',
                'official_name' => 'Government College University, Faisalabad',
                'slug' => 'government-college-university-faisalabad',
                'institution_type' => 'public university',
            ],
            'normalized_payload' => [
                'name' => 'Government College University',
                'official_name' => 'Government College University, Faisalabad',
                'slug' => 'government-college-university-faisalabad',
                'institution_type' => 'public university',
            ],
        ]);

        $duplicate = app(ImportDuplicateDetectorService::class)->findInstitution(
            'government-college-university-lahore',
            null,
            [],
            [
                'name' => 'Government College University',
                'official_name' => 'Government College University, Lahore',
                'slug' => 'government-college-university-lahore',
                'institution_type' => 'public university',
            ]
        );

        $this->assertNull($duplicate);
    }


    public function test_same_slug_does_not_override_conflicting_official_names_in_canonical_or_staged_records(): void
    {
        $canonical = Institution::query()->create([
            'name' => 'Government College University',
            'official_name' => 'Government College University, Faisalabad',
            'short_name' => 'GCUF',
            'slug' => 'government-college-university-lahore',
            'institution_type' => 'public university',
            'status' => 'candidate',
        ]);

        $source = Source::create([
            'source_type' => 'official',
            'name' => 'HEC GCU identity',
            'url' => 'https://example.gov.pk/gcu',
            'authority_scope' => 'official university identity',
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => 'agent-gcu-conflicting-official-name',
            'entity_type' => 'institution',
            'source_type' => 'automated_discovery',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 1,
            'candidate_rows' => 1,
            'received_at' => now(),
        ]);
        ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'unresolved',
            'payload' => [
                'name' => 'Government College University',
                'official_name' => 'Government College University, Faisalabad',
                'slug' => 'government-college-university-lahore',
                'institution_type' => 'public university',
            ],
            'normalized_payload' => [
                'name' => 'Government College University',
                'official_name' => 'Government College University, Faisalabad',
                'slug' => 'government-college-university-lahore',
                'institution_type' => 'public university',
            ],
        ]);

        $duplicate = app(ImportDuplicateDetectorService::class)->findInstitution(
            'government-college-university-lahore',
            null,
            [],
            [
                'name' => 'Government College University',
                'official_name' => 'Government College University, Lahore',
                'slug' => 'government-college-university-lahore',
                'institution_type' => 'public university',
            ]
        );

        $this->assertSame('Government College University, Faisalabad', $canonical->fresh()->official_name);
        $this->assertNull($duplicate);
    }

}

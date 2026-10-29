<?php

namespace Tests\Feature;

use App\Models\Assertion;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Services\InstituteDiscoveryAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class InstituteDiscoveryInstitutionIdentityTest extends TestCase
{
    use RefreshDatabase;

    private const PROFILE = 'gcu-lahore-institution-identity';
    private const URL = 'https://www.hec.gov.pk/english/universities/Pages/Punjab/Government-College-University%2C-Lahore.aspx';

    public function test_official_institution_identity_is_staged_with_provenance_without_a_canonical_parent_and_reuses_identical_batch(): void
    {
        Storage::fake('local');
        $html = '<html><head><title>Government College University, Lahore — HEC</title></head><body>'
            .'<h1>Government College University, Lahore</h1><p>Government College University, Lahore is a public university.</p>'
            .'</body></html>';

        Http::fake([self::URL => Http::response($html, 200, ['Content-Type' => 'text/html'])]);

        $this->assertSame(0, Institution::count());
        $first = app(InstituteDiscoveryAgentService::class)->run(self::PROFILE);

        $this->assertSame('institution', $first['entity_type']);
        $this->assertNull($first['institution_id']);
        $this->assertSame(1, $first['candidate_rows']);
        $this->assertSame(0, Institution::count());

        $row = ImportRow::query()->where('import_batch_id', $first['batch_id'])->firstOrFail();
        $payload = $row->normalized_payload;
        $this->assertSame('candidate', $row->status);
        $this->assertNull($row->parent_subject_id);
        $this->assertNull($row->parent_subject_type);
        $this->assertSame('Government College University Lahore', $payload['name']);
        $this->assertSame('Government College University, Lahore', $payload['official_name']);
        $this->assertSame('GCU', $payload['short_name']);
        $this->assertSame('government-college-university-lahore', $payload['slug']);
        $this->assertSame('public university', $payload['institution_type']);
        $this->assertSame(self::URL, $payload['_provenance']['source_url']);
        $this->assertSame(hash('sha256', $html), $payload['_provenance']['content_hash']);
        $this->assertSame($first['snapshot_id'], $payload['_provenance']['snapshot_id']);
        $this->assertSame($first['evidence_id'], $payload['_provenance']['evidence_id']);
        $this->assertSame('5', $payload['_provenance']['extractor_version']);
        $this->assertNotEmpty($payload['_provenance']['captured_at']);

        $this->assertDatabaseHas('source_snapshots', ['id' => $first['snapshot_id'], 'content_hash' => hash('sha256', $html)]);
        $this->assertDatabaseHas('evidence', ['id' => $first['evidence_id'], 'source_id' => $first['source_id'], 'snapshot_id' => $first['snapshot_id']]);
        $this->assertDatabaseHas('import_batches', ['id' => $first['batch_id'], 'entity_type' => 'institution', 'status' => 'validated']);
        $this->assertDatabaseHas('assertions', ['evidence_id' => $first['evidence_id'], 'subject_type' => ImportRow::class, 'subject_id' => $row->id]);

        $second = app(InstituteDiscoveryAgentService::class)->run(self::PROFILE);
        $this->assertSame($first['batch_id'], $second['batch_id']);
        $this->assertSame(1, ImportBatch::query()->where('entity_type', 'institution')->count());
        $this->assertSame(1, ImportRow::query()->where('import_batch_id', $first['batch_id'])->count());
        $this->assertSame(0, Institution::count());
    }

    public function test_missing_institution_identity_configuration_fails_before_source_or_batch_writes(): void
    {
        config()->set('institute_agent.profiles.'.self::PROFILE.'.institution_identity', [
            'name' => 'Incomplete University',
            'identity_terms' => ['Incomplete University'],
        ]);

        try {
            app(InstituteDiscoveryAgentService::class)->run(self::PROFILE);
            $this->fail('Invalid institution identity configuration must fail closed.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('valid institution_identity configuration', $exception->getMessage());
            $this->assertStringContainsString('official_name', $exception->getMessage());
        }

        $this->assertDatabaseCount('sources', 0);
        $this->assertDatabaseCount('source_snapshots', 0);
        $this->assertDatabaseCount('evidence', 0);
        $this->assertDatabaseCount('import_batches', 0);
        $this->assertDatabaseCount('import_rows', 0);
    }

    public function test_program_discovery_still_requires_an_existing_canonical_parent(): void
    {
        $this->assertSame(0, Institution::count());

        try {
            app(InstituteDiscoveryAgentService::class)->run('gcu-lahore-programs');
            $this->fail('Program discovery must require an existing canonical parent institution.');
        } catch (\\RuntimeException $exception) {
            $this->assertStringContainsString('requires an existing parent institution', $exception->getMessage());
        }

        $this->assertDatabaseCount('sources', 0);
        $this->assertDatabaseCount('source_snapshots', 0);
        $this->assertDatabaseCount('evidence', 0);
        $this->assertDatabaseCount('import_batches', 0);
        $this->assertDatabaseCount('import_rows', 0);
    }

}

<?php

namespace Tests\Feature;

use App\Models\Assertion;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Source;
use App\Services\InstituteDiscoveryAgentService;
use App\Services\InstituteIdentityDiscoveryService;
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

    public function test_top_level_identity_discovery_serializes_candidate_persistence_per_source(): void
    {
        Storage::fake('local');
        $html = '<html><head><title>Government College University, Lahore — HEC</title></head><body>'
            .'<h1>Government College University, Lahore</h1><p>Government College University, Lahore is a public university.</p>'
            .'</body></html>';
        Http::fake([self::URL => Http::sequence()
            ->push($html, 200, ['Content-Type' => 'text/html'])
            ->push($html.'<footer>Updated page chrome.</footer>', 200, ['Content-Type' => 'text/html'])]);

        $service = app(InstituteIdentityDiscoveryService::class);
        $definition = config('institute_agent.profiles.'.self::PROFILE);
        $firstSource = \App\Models\Source::query()->updateOrCreate(
            ['url' => self::URL],
            ['source_type' => 'official', 'name' => 'HEC GCU identity', 'authority_scope' => 'HEC identity', 'status' => 'active']
        );
        $first = $service->run(self::PROFILE, $firstSource, $definition);
        $second = $service->run(self::PROFILE, $firstSource, $definition);

        $source = \App\Models\Source::query()->findOrFail($first['source_id']);
        $this->assertSame($source->id, $second['source_id']);
        $this->assertNotSame($first['snapshot_id'], $second['snapshot_id']);
        $this->assertSame(1, ImportRow::query()->where('import_batch_id', $first['batch_id'])->where('status', 'candidate')->count());
        $this->assertSame(1, ImportRow::query()->where('import_batch_id', $second['batch_id'])->where('status', 'duplicate')->count());
        $this->assertSame(2, ImportBatch::query()->where('source_id', $source->id)->where('entity_type', 'institution')->count());
        $this->assertSame(1, ImportRow::query()->whereHas('batch', fn ($query) => $query->where('source_id', $source->id)->where('entity_type', 'institution'))->where('status', 'candidate')->count());
        $this->assertSame(1, ImportRow::query()->whereHas('batch', fn ($query) => $query->where('source_id', $source->id)->where('entity_type', 'institution'))->where('status', 'duplicate')->count());
        $this->assertSame(0, Institution::query()->count());
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
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('requires an existing parent institution', $exception->getMessage());
        }

        $this->assertDatabaseCount('sources', 0);
        $this->assertDatabaseCount('source_snapshots', 0);
        $this->assertDatabaseCount('evidence', 0);
        $this->assertDatabaseCount('import_batches', 0);
        $this->assertDatabaseCount('import_rows', 0);
    }


    public function test_campus_discovery_requires_canonical_parent_before_any_source_or_staging_writes(): void
    {
        Http::fake();

        try {
            app(InstituteDiscoveryAgentService::class)->run('cui-lahore-campus');
            $this->fail('Campus discovery must fail closed without a canonical parent institution.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('requires an existing parent institution', $exception->getMessage());
        }

        Http::assertNothingSent();
        $this->assertDatabaseCount('sources', 0);
        $this->assertDatabaseCount('source_snapshots', 0);
        $this->assertDatabaseCount('evidence', 0);
        $this->assertDatabaseCount('import_batches', 0);
        $this->assertDatabaseCount('import_rows', 0);
    }

    public function test_existing_canonical_institution_slug_is_marked_duplicate_without_mutation(): void
    {
        Storage::fake('local');
        $canonical = Institution::create([
            'name' => 'Government College University Lahore',
            'slug' => 'government-college-university-lahore',
            'status' => 'active',
        ]);
        $before = $canonical->only(['name', 'slug', 'status']);
        $html = '<html><head><title>Government College University, Lahore — HEC</title></head><body>'
            .'<h1>Government College University, Lahore</h1><p>Government College University, Lahore is a public university.</p>'
            .'</body></html>';
        Http::fake([self::URL => Http::response($html, 200, ['Content-Type' => 'text/html'])]);

        $summary = app(InstituteDiscoveryAgentService::class)->run(self::PROFILE);
        $row = ImportRow::query()->where('import_batch_id', $summary['batch_id'])->firstOrFail();

        $this->assertSame('duplicate', $row->status);
        $this->assertSame('duplicate', $row->match_state);
        $this->assertStringContainsString('already exists in canonical data', implode(' ', $row->validation_errors ?? []));
        $this->assertSame($before, $canonical->fresh()->only(['name', 'slug', 'status']));
        $this->assertSame(1, Institution::count());
        $this->assertSame(1, ImportRow::where('import_batch_id', $summary['batch_id'])->count());
    }

    public function test_previously_staged_institution_slug_is_marked_duplicate(): void
    {
        Storage::fake('local');
        $firstHtml = '<html><head><title>Government College University, Lahore — HEC</title></head><body>'
            .'<h1>Government College University, Lahore</h1><p>Government College University, Lahore is a public university.</p>'
            .'</body></html>';
        Http::fake([self::URL => Http::sequence()
            ->push($firstHtml, 200, ['Content-Type' => 'text/html'])
            ->push($firstHtml.'<p>Updated page content.</p>', 200, ['Content-Type' => 'text/html'])]);

        $first = app(InstituteDiscoveryAgentService::class)->run(self::PROFILE);
        $firstRow = ImportRow::where('import_batch_id', $first['batch_id'])->firstOrFail();
        $this->assertSame('candidate', $firstRow->status);

        // A changed response from the same configured source produces a separate immutable snapshot and batch.
        $second = app(InstituteDiscoveryAgentService::class)->run(self::PROFILE);
        $secondRow = ImportRow::where('import_batch_id', $second['batch_id'])->firstOrFail();
        $this->assertSame('duplicate', $secondRow->status);
        $this->assertStringContainsString('already staged', implode(' ', $secondRow->validation_errors ?? []));
        $this->assertNotSame($first['batch_id'], $second['batch_id']);
        $this->assertSame(2, ImportBatch::where('entity_type', 'institution')->count());
        $this->assertSame('candidate', $firstRow->fresh()->status);
        $this->assertSame(0, Institution::count());
    }

    public function test_repeated_institution_slug_within_one_source_batch_is_marked_duplicate(): void
    {
        Storage::fake('local');
        $html = '<html><head><title>Government College University, Lahore</title></head><body>'
            .'<h1>Government College University, Lahore</h1></body></html>';
        Http::fake([self::URL => Http::response($html, 200, ['Content-Type' => 'text/html'])]);

        // Institution profiles currently extract one configured identity. Supply two
        // deterministic extractor results to exercise the generic same-batch duplicate guard.
        app()->instance(\App\Services\InstituteDiscoveryCandidateExtractor::class, new class {
            public function extract(string $entityType, string $html, array $identity = []): array
            {
                return [
                    [
                        'name' => 'Government College University Lahore',
                        'official_name' => 'Government College University, Lahore',
                        'short_name' => 'GCU',
                        'slug' => 'government-college-university-lahore',
                        'institution_type' => 'public university',
                        'status' => 'candidate',
                        'confidence' => 0.98,
                        'evidence_excerpt' => 'Government College University, Lahore',
                    ],
                    [
                        'name' => 'Government College University Lahore',
                        'official_name' => 'Government College University, Lahore',
                        'short_name' => 'GCU',
                        'slug' => 'government-college-university-lahore',
                        'institution_type' => 'public university',
                        'status' => 'candidate',
                        'confidence' => 0.98,
                        'evidence_excerpt' => 'Government College University Lahore',
                    ],
                ];
            }
        });

        $summary = app(InstituteDiscoveryAgentService::class)->run(self::PROFILE);
        $rows = ImportRow::where('import_batch_id', $summary['batch_id'])->orderBy('row_number')->get();

        $this->assertCount(2, $rows);
        $this->assertSame('candidate', $rows[0]->status);
        $this->assertSame('duplicate', $rows[1]->status);
        $this->assertStringContainsString('same normalized slug', implode(' ', $rows[1]->validation_errors ?? []));
        $this->assertSame(0, Institution::count());
    }

    public function test_different_institution_slug_is_not_classified_as_duplicate(): void
    {
        Storage::fake('local');
        $this->createInstitutionCandidateStagingRow('other-university-slug');
        $profile = $this->profileWithIdentity([
            'name' => 'Distinct University',
            'official_name' => 'Distinct University',
            'short_name' => 'DU',
            'slug' => 'distinct-university',
            'institution_type' => 'public university',
            'identity_terms' => ['Distinct University'],
        ]);
        $html = '<html><head><title>Distinct University</title></head><body><h1>Distinct University</h1></body></html>';
        Http::fake([$profile['source']['url'] => Http::response($html, 200, ['Content-Type' => 'text/html'])]);

        $summary = app(InstituteDiscoveryAgentService::class)->run(self::PROFILE);
        $row = ImportRow::where('import_batch_id', $summary['batch_id'])->firstOrFail();

        $this->assertSame('candidate', $row->status);
        $this->assertNull($row->validation_errors);
        $this->assertSame(0, Institution::count());
    }

    public function test_explicit_refresh_requires_valid_profile_and_source_specific_superseded_batch(): void
    {
        Storage::fake('local');
        $html = '<html><head><title>Government College University, Lahore — HEC</title></head><body>'
            .'<h1>Government College University, Lahore</h1></body></html>';
        Http::fake([self::URL => Http::response($html, 200, ['Content-Type' => 'text/html'])]);
        $summary = app(InstituteDiscoveryAgentService::class)->run(self::PROFILE);

        try {
            app(InstituteDiscoveryAgentService::class)->run(self::PROFILE, false, true);
            $this->fail('Explicit refresh must name the superseded batch.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('requires a superseded batch ID', $exception->getMessage());
        }

        $unrelated = ImportBatch::create([
            'batch_key' => 'agent-unrelated-profile-'.uniqid(),
            'entity_type' => 'institution',
            'source_type' => 'automated_discovery',
            'source_id' => $summary['source_id'],
            'status' => 'validated',
            'received_at' => now(),
        ]);
        try {
            app(InstituteDiscoveryAgentService::class)->run(self::PROFILE, false, true, $unrelated->id);
            $this->fail('Refresh must reject a batch with a different profile key.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('must belong to this profile and source', $exception->getMessage());
        }

        $wrongSource = \App\Models\Source::create([
            'source_type' => 'official',
            'name' => 'Unrelated source',
            'url' => 'https://example.edu/unrelated',
            'authority_scope' => 'unrelated source',
            'status' => 'active',
        ]);
        $wrongSourceBatch = ImportBatch::create([
            'batch_key' => 'agent-'.self::PROFILE.'-wrong-source',
            'entity_type' => 'institution',
            'source_type' => 'automated_discovery',
            'source_id' => $wrongSource->id,
            'status' => 'validated',
            'received_at' => now(),
        ]);
        try {
            app(InstituteDiscoveryAgentService::class)->run(self::PROFILE, false, true, $wrongSourceBatch->id);
            $this->fail('Refresh must reject a batch associated with another source.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('must belong to this profile and source', $exception->getMessage());
        }
        $this->assertSame(0, Institution::count());
    }

    public function test_changed_source_explicitly_supersedes_history_without_canonical_mutation(): void
    {
        Storage::fake('local');
        $firstHtml = '<html><head><title>Government College University, Lahore — HEC</title></head><body>'
            .'<h1>Government College University, Lahore</h1><p>Original official content.</p></body></html>';
        $changedHtml = '<html><head><title>Government College University, Lahore — HEC</title></head><body>'
            .'<h1>Government College University, Lahore</h1><p>Changed official content.</p></body></html>';
        Http::fake([self::URL => Http::sequence()
            ->push($firstHtml, 200, ['Content-Type' => 'text/html'])
            ->push($changedHtml, 200, ['Content-Type' => 'text/html'])]);

        $first = app(InstituteDiscoveryAgentService::class)->run(self::PROFILE);
        $oldRow = ImportRow::where('import_batch_id', $first['batch_id'])->firstOrFail();
        $refresh = app(InstituteDiscoveryAgentService::class)->run(self::PROFILE, false, true, $first['batch_id']);
        $newRow = ImportRow::where('import_batch_id', $refresh['batch_id'])->firstOrFail();

        $this->assertNotSame($first['batch_id'], $refresh['batch_id']);
        $this->assertNotSame($first['snapshot_id'], $refresh['snapshot_id']);
        $this->assertSame('candidate', $oldRow->fresh()->status);
        $this->assertSame('candidate', $newRow->status);
        $this->assertDatabaseHas('source_snapshots', ['id' => $first['snapshot_id'], 'content_hash' => hash('sha256', $firstHtml)]);
        $this->assertDatabaseHas('source_snapshots', ['id' => $refresh['snapshot_id'], 'content_hash' => hash('sha256', $changedHtml)]);
        $this->assertDatabaseHas('evidence', ['id' => $first['evidence_id'], 'snapshot_id' => $first['snapshot_id']]);
        $this->assertDatabaseHas('evidence', ['id' => $refresh['evidence_id'], 'snapshot_id' => $refresh['snapshot_id']]);
        $this->assertStringContainsString('refresh_of_batch_id='.$first['batch_id'], $this->batchNotes($refresh['batch_id']));
        $this->assertSame(2, ImportBatch::where('entity_type', 'institution')->count());
        $this->assertSame(0, Institution::count());
    }

    public function test_institution_candidate_remains_review_only_with_staged_row_evidence_and_no_canonical_publication(): void
    {
        Storage::fake('local');
        $html = '<html><head><title>Government College University, Lahore — HEC</title></head><body>'
            .'<h1>Government College University, Lahore</h1></body></html>';
        Http::fake([self::URL => Http::response($html, 200, ['Content-Type' => 'text/html'])]);

        $summary = app(InstituteDiscoveryAgentService::class)->run(self::PROFILE);
        $row = ImportRow::where('import_batch_id', $summary['batch_id'])->firstOrFail();
        $manifest = json_decode(Storage::disk('local')->get($summary['review_package']['manifest_path']), true);

        $this->assertSame('candidate', $row->status);
        $this->assertDatabaseHas('assertions', [
            'evidence_id' => $summary['evidence_id'],
            'subject_type' => ImportRow::class,
            'subject_id' => $row->id,
        ]);
        $this->assertSame('review_required', $manifest['status']);
        $this->assertFalse($manifest['canonical_mutations_performed']);
        $this->assertFalse($manifest['automatic_approval_performed']);
        $this->assertDatabaseCount('import_promotion_decisions', 0);
        $this->assertDatabaseCount('identity_resolution_decisions', 0);
        $this->assertSame(0, Institution::count());
    }

    private function profileWithUniqueSourceUrl(string $url): array
    {
        $profile = config('institute_agent.profiles.'.self::PROFILE);
        $profile['source']['url'] = $url;
        config()->set('institute_agent.profiles.'.self::PROFILE, $profile);
        return $profile;
    }

    private function profileWithIdentityTerms(array $terms): array
    {
        $profile = config('institute_agent.profiles.'.self::PROFILE);
        $profile['institution_identity']['identity_terms'] = $terms;
        config()->set('institute_agent.profiles.'.self::PROFILE, $profile);
        return $profile;
    }

    private function profileWithIdentity(array $identity): array
    {
        $profile = config('institute_agent.profiles.'.self::PROFILE);
        $profile['institution_identity'] = $identity;
        config()->set('institute_agent.profiles.'.self::PROFILE, $profile);
        return $profile;
    }

    private function createInstitutionCandidateStagingRow(string $slug): ImportRow
    {
        $source = \App\Models\Source::create([
            'source_type' => 'official',
            'name' => 'Old official source',
            'url' => 'https://example.edu/old',
            'authority_scope' => 'official identity listing',
            'status' => 'active',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => 'agent-old-institution-'.uniqid(),
            'entity_type' => 'institution',
            'source_type' => 'automated_discovery',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 1,
            'received_at' => now(),
        ]);
        return ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'unresolved',
            'parent_subject_type' => null,
            'parent_subject_id' => null,
            'payload' => ['name' => 'Previous University', 'slug' => $slug],
            'normalized_payload' => ['name' => 'Previous University', 'slug' => $slug],
        ]);
    }

    private function batchNotes(int $batchId): string
    {
        return (string) ImportBatch::query()->findOrFail($batchId)->notes;
    }

}

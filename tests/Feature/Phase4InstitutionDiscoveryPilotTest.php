<?php

namespace Tests\Feature;

use App\Models\Evidence;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Source;
use App\Models\SourceRetrievalRun;
use App\Models\SourceSnapshot;
use App\Services\InstituteDiscoveryAgentService;
use App\Services\SourceRetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase4InstitutionDiscoveryPilotTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_configured_identity_profiles_stage_only_synthetic_candidates_with_linked_provenance(): void
    {
        Storage::fake('local');
        $profiles = array_filter(
            config('institute_agent.profiles', []),
            static fn (array $definition): bool => ($definition['entity_type'] ?? null) === 'institution'
        );

        $this->assertCount(21, $profiles);
        $this->assertSame(
            count($profiles),
            count(array_unique(array_column(array_map(
                static fn (array $definition): array => $definition['institution_identity'],
                $profiles
            ), 'slug'))
        );
        $sourceUrls = array_map(static fn (array $definition): string => $definition['source']['url'], $profiles);
        $this->assertSame(count($profiles), count(array_unique($sourceUrls)));

        foreach ($profiles as $profile => $definition) {
            $this->assertStringStartsWith('https://www.hec.gov.pk/english/universities/Pages/', $definition['source']['url'], $profile);
            $this->assertContains($definition['institution_identity']['institution_type'], ['public university', 'private university'], $profile);
            $this->assertFalse($definition['monitoring']['use_ai'], $profile);
            if (str_starts_with((string) $profile, 'hec-')) {
                $this->assertFalse($definition['monitoring']['enabled'], $profile);
                $this->assertFalse($definition['monitoring']['auto_discovery'], $profile);
            }
        }

        $responses = [];
        $expected = [];
        foreach (array_values($profiles) as $index => $definition) {
            $identity = $definition['institution_identity'];
            $term = $identity['identity_terms'][0];
            $escapedTerm = htmlspecialchars($term, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            // Three common, synthetic layouts: title + body, application-name metadata,
            // and main-content heading. These are fixtures, not captured source HTML.
            $html = match ($index % 3) {
                0 => '<!doctype html><html><head><title>'.$escapedTerm.' — HEC</title></head><body><h1>'.$escapedTerm.'</h1><p>'.$escapedTerm.' appears in this synthetic official-source fixture.</p></body></html>',
                1 => '<!doctype html><html><head><meta name="application-name" content="'.$escapedTerm.'"></head><body><main><p>'.$escapedTerm.' appears in this synthetic official-source fixture.</p></main></body></html>',
                default => '<!doctype html><html><body><main><h2>'.$escapedTerm.'</h2><p>'.$escapedTerm.' appears in this synthetic official-source fixture.</p></main></body></html>',
            };
            $url = $definition['source']['url'];
            $responses[$url] = Http::response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
            $expected[$url] = ['html' => $html, 'profile' => null];
        }

        Http::fake($responses);

        $agent = app(InstituteDiscoveryAgentService::class);
        $staged = 0;
        foreach ($profiles as $profile => $definition) {
            $summary = $agent->run((string) $profile);
            $url = $definition['source']['url'];
            $html = $expected[$url]['html'];

            $this->assertSame('institution', $summary['entity_type'], $profile);
            $this->assertSame(1, $summary['candidate_rows'], $profile);
            $this->assertSame(0, $summary['duplicate_rows'], $profile);
            $this->assertSame(hash('sha256', $html), SourceSnapshot::findOrFail($summary['snapshot_id'])->content_hash, $profile);

            $row = ImportRow::query()->where('import_batch_id', $summary['batch_id'])->firstOrFail();
            $payload = $row->normalized_payload;
            $this->assertSame('candidate', $row->status, $profile);
            $this->assertNull($row->parent_subject_id, $profile);
            $this->assertSame($definition['institution_identity']['slug'], $payload['slug'], $profile);
            $this->assertSame($url, data_get($payload, '_provenance.source_url'), $profile);
            $this->assertSame($summary['snapshot_id'], data_get($payload, '_provenance.snapshot_id'), $profile);
            $this->assertSame($summary['evidence_id'], data_get($payload, '_provenance.evidence_id'), $profile);
            $this->assertSame(hash('sha256', $html), data_get($payload, '_provenance.content_hash'), $profile);
            $manifest = json_decode(Storage::disk('local')->get($summary['review_package']['manifest_path']), true);
            $this->assertFalse($manifest['canonical_mutations_performed'], $profile);
            $this->assertFalse($manifest['automatic_approval_performed'], $profile);
            $this->assertDatabaseHas('evidence', [
                'id' => $summary['evidence_id'],
                'source_id' => $summary['source_id'],
                'snapshot_id' => $summary['snapshot_id'],
                'content_hash' => hash('sha256', $html),
            ]);
            $this->assertDatabaseHas('import_batches', [
                'id' => $summary['batch_id'],
                'entity_type' => 'institution',
                'source_type' => 'automated_discovery',
                'status' => 'validated',
            ]);
            $staged++;
        }

        $this->assertSame(21, $staged);
        $this->assertSame(21, Source::query()->count());
        $this->assertSame(21, SourceSnapshot::query()->count());
        $this->assertSame(21, Evidence::query()->count());
        $this->assertSame(21, ImportBatch::query()->where('entity_type', 'institution')->count());
        $this->assertSame(21, ImportRow::query()->whereHas('batch', fn ($query) => $query->where('entity_type', 'institution'))->count());
        $this->assertSame(0, Institution::query()->count());
        $this->assertSame(0, ImportBatch::query()->whereIn('status', ['promoted', 'published'])->count());
    }

    public function test_source_retrieval_http_failure_is_persisted_with_status_and_content_type_without_a_snapshot(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Synthetic HEC-style source',
            'url' => 'https://8.8.8.8/phase4-source-failure',
            'authority_scope' => 'synthetic test source only',
            'status' => 'active',
        ]);
        Http::fake([
            'https://8.8.8.8/phase4-source-failure' => Http::response(
                '<html><body>Access denied</body></html>',
                403,
                ['Content-Type' => 'text/html; charset=UTF-8']
            ),
        ]);

        $run = app(SourceRetrievalService::class)->retrieve($source);

        $this->assertSame('failed', $run->status);
        $this->assertSame(403, (int) $run->http_status);
        $this->assertSame('text/html; charset=UTF-8', $run->content_type);
        $this->assertStringContainsString('HTTP 403', $run->error_message);
        $this->assertNotNull($run->completed_at);
        $this->assertNull($run->snapshot_id);
        $this->assertNull($run->content_hash);
        $this->assertDatabaseHas('source_retrieval_runs', [
            'id' => $run->id,
            'source_id' => $source->id,
            'status' => 'failed',
            'http_status' => 403,
            'content_type' => 'text/html; charset=UTF-8',
        ]);
        $this->assertSame(0, SourceSnapshot::query()->count());
        $this->assertSame(0, Evidence::query()->count());
        $this->assertSame(0, ImportBatch::query()->count());
        $this->assertSame(0, Institution::query()->count());
    }
}

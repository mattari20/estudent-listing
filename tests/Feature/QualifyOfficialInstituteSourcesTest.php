<?php

namespace Tests\Feature;

use App\Services\SourceUrlGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QualifyOfficialInstituteSourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(SourceUrlGuard::class, new SourceUrlGuard(
            static fn (string $host, int $type): array => [['host' => $host, 'type' => 'A', 'ip' => '93.184.216.34']]
        ));
        Storage::fake('local');
    }

    public function test_qualification_generates_live_fingerprint_and_extracts_identity_without_database_writes(): void
    {
        config([
            'institute_agent.profiles.qualification-fixture' => [
                'source' => ['url' => 'https://example.test/gcu'],
                'institution_identity' => [
                    'name' => 'Government College University Lahore',
                    'official_name' => 'Government College University, Lahore',
                    'short_name' => 'GCU Lahore',
                    'slug' => 'government-college-university-lahore',
                    'institution_type' => 'public university',
                    'identity_terms' => ['Government College University, Lahore'],
                ],
            ],
            'institute_source_qualification.test' => [
                ['id' => 'gcu-fixture', 'profile' => 'qualification-fixture', 'authority' => 'test authority'],
            ],
        ]);
        Http::fake([
            'https://example.test/gcu' => Http::response(
                '<html><head><title>Government College University, Lahore</title></head><body>Government College University, Lahore</body></html>',
                200,
                ['Content-Type' => 'text/html; charset=UTF-8']
            ),
        ]);

        $tables = [
            'sources', 'source_snapshots', 'evidences', 'import_batches', 'import_rows',
            'source_retrieval_runs', 'source_monitoring_policies', 'source_change_observations',
            'source_change_assertion_links', 'monitoring_alerts', 'assertions', 'identity_match_candidates',
            'identity_resolution_decisions', 'identity_conflicts', 'verification_decisions',
            'provenance_review_events', 'import_promotion_decisions', 'import_promotion_events',
            'institution_field_approvals', 'official_source_refresh_events',
            'structured_enrichment_promotion_events', 'data_quality_events', 'change_events',
            'institutions', 'campuses', 'programs', 'program_offerings',
        ];
        $before = [];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $before[$table] = \Illuminate\Support\Facades\DB::table($table)->count();
            }
        }

        $exit = Artisan::call('institutes:qualify-official-sources', [
            '--stage' => 'test',
            '--delay-ms' => 0,
            '--output' => 'source-qualification/test-report.json',
        ]);

        $this->assertSame(0, $exit, Artisan::output());
        $after = [];
        foreach ($before as $table => $count) {
            $after[$table] = \Illuminate\Support\Facades\DB::table($table)->count();
        }
        $this->assertSame($before, $after);

        $report = json_decode(Storage::disk('local')->get('source-qualification/test-report.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(1, $report['source_count']);
        $this->assertSame(1, $report['live_retrieval_successes']);
        $this->assertSame(1, $report['extraction_successes']);
        $this->assertSame(0, $report['identity_qualified_sources']);
        $this->assertSame('EXTRACTION_QUALIFIED', $report['results'][0]['qualification_level']);
        $this->assertSame('NOT_RUN_NO_CANONICAL_OR_STAGED_FIXTURE_INPUT', $report['results'][0]['identity_validation_status']);
        $this->assertFalse($report['database_writes_performed']);
        $this->assertSame(200, $report['results'][0]['http_status']);
        $this->assertSame('https://example.test/gcu', $report['results'][0]['final_url']);
        $this->assertSame(hash('sha256', '<html><head><title>Government College University, Lahore</title></head><body>Government College University, Lahore</body></html>'), $report['results'][0]['raw_sha256']);
        $this->assertSame('Government College University Lahore', $report['results'][0]['institutions'][0]['name']);
        Http::assertSentCount(1);
    }

    public function test_non_html_response_is_fingerprinted_but_not_extracted(): void
    {
        config(['institute_source_qualification.test' => [
            ['id' => 'not-html', 'url' => 'https://example.test/data', 'authority' => 'test authority', 'extractor' => 'unsupported-directory'],
        ]]);
        Http::fake([
            'https://example.test/data' => Http::response('{"error":"not html"}', 200, ['Content-Type' => 'application/json']),
        ]);

        $exit = Artisan::call('institutes:qualify-official-sources', ['--stage' => 'test', '--delay-ms' => 0, '--output' => 'source-qualification/non-html.json']);
        $report = json_decode(Storage::disk('local')->get('source-qualification/non-html.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertSame(200, $report['results'][0]['http_status']);
        $this->assertNotNull($report['results'][0]['raw_sha256']);
        $this->assertSame(0, $report['results'][0]['extracted_count']);
        $this->assertSame('BLOCKED', $report['results'][0]['qualification_level']);
        $this->assertStringContainsString('Expected an HTML content type', $report['results'][0]['error']);
    }

    public function test_source_filter_restricts_a_stage_to_one_configured_source_and_preserves_no_write_guarantee(): void
    {
        config([
            'institute_agent.profiles.qualification-gcu' => [
                'source' => ['url' => 'https://example.test/gcu'],
                'institution_identity' => [
                    'name' => 'Government College University Lahore',
                    'official_name' => 'Government College University, Lahore',
                    'short_name' => 'GCU Lahore',
                    'slug' => 'government-college-university-lahore',
                    'institution_type' => 'public university',
                    'identity_terms' => ['Government College University, Lahore'],
                ],
            ],
            'institute_agent.profiles.qualification-other' => [
                'source' => ['url' => 'https://example.test/other'],
                'institution_identity' => [
                    'name' => 'Other University',
                    'official_name' => 'Other University',
                    'short_name' => 'Other',
                    'slug' => 'other-university',
                    'institution_type' => 'public university',
                    'identity_terms' => ['Other University'],
                ],
            ],
            'institute_source_qualification.test' => [
                ['id' => 'gcu-lahore', 'profile' => 'qualification-gcu', 'authority' => 'HEC university identity'],
                ['id' => 'other-university', 'profile' => 'qualification-other', 'authority' => 'HEC university identity'],
            ],
        ]);
        Http::fake([
            'https://example.test/gcu' => Http::response(
                '<html><head><title>Government College University, Lahore</title></head><body>Government College University, Lahore</body></html>',
                200,
                ['Content-Type' => 'text/html; charset=UTF-8']
            ),
            'https://example.test/other' => Http::response(
                '<html><head><title>Other University</title></head><body>Other University</body></html>',
                200,
                ['Content-Type' => 'text/html; charset=UTF-8']
            ),
        ]);

        $tables = [
            'sources', 'source_snapshots', 'evidences', 'import_batches', 'import_rows',
            'source_retrieval_runs', 'source_monitoring_policies', 'source_change_observations',
            'identity_resolution_decisions', 'verification_decisions',
            'institutions', 'campuses', 'programs', 'program_offerings',
        ];
        $before = [];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $before[$table] = \\Illuminate\\Support\\Facades\\DB::table($table)->count();
            }
        }

        $exit = Artisan::call('institutes:qualify-official-sources', [
            '--stage' => 'test',
            '--source' => 'gcu-lahore',
            '--delay-ms' => 0,
            '--output' => 'source-qualification/gcu-only-test.json',
        ]);

        $this->assertSame(0, $exit, Artisan::output());
        $after = [];
        foreach ($before as $table => $count) {
            $after[$table] = \\Illuminate\\Support\\Facades\\DB::table($table)->count();
        }
        $this->assertSame($before, $after);

        $report = json_decode(Storage::disk('local')->get('source-qualification/gcu-only-test.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('test', $report['stage']);
        $this->assertSame('gcu-lahore', $report['source_filter']);
        $this->assertSame(1, $report['source_count']);
        $this->assertSame(['gcu-lahore'], array_column($report['results'], 'source_id'));
        $this->assertSame(1, $report['live_retrieval_successes']);
        $this->assertSame(1, $report['extraction_successes']);
        $this->assertFalse($report['database_writes_performed']);
        $this->assertFalse($report['discovery_or_canonical_mutations_performed']);
        Http::assertSentCount(1);
        Http::assertSent(static fn ($request): bool => $request->url() === 'https://example.test/gcu');
    }

    public function test_unknown_source_filter_is_rejected_before_any_http_request_or_report_write(): void
    {
        config(['institute_source_qualification.test' => [
            ['id' => 'gcu-lahore', 'url' => 'https://example.test/gcu', 'authority' => 'HEC university identity', 'extractor' => 'unsupported-directory'],
        ]]);
        Http::fake();

        $exit = Artisan::call('institutes:qualify-official-sources', [
            '--stage' => 'test',
            '--source' => 'not-configured',
            '--delay-ms' => 0,
            '--output' => 'source-qualification/invalid-filter.json',
        ]);

        $this->assertSame(2, $exit, Artisan::output());
        $this->assertStringContainsString('Unknown or ambiguous source id', Artisan::output());
        Http::assertNothingSent();
        $this->assertFalse(Storage::disk('local')->exists('source-qualification/invalid-filter.json'));
    }

    public function test_invalid_url_is_blocked_before_any_http_request(): void
    {
        config(['institute_source_qualification.test' => [
            ['id' => 'unsafe', 'url' => 'http://127.0.0.1/private', 'authority' => 'test authority', 'extractor' => 'unsupported-directory'],
        ]]);
        Http::fake();

        $exit = Artisan::call('institutes:qualify-official-sources', ['--stage' => 'test', '--delay-ms' => 0, '--output' => 'source-qualification/unsafe.json']);
        $report = json_decode(Storage::disk('local')->get('source-qualification/unsafe.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertSame('BLOCKED', $report['results'][0]['qualification_level']);
        $this->assertStringContainsString('not publicly routable', $report['results'][0]['error']);
        Http::assertNothingSent();
    }
}

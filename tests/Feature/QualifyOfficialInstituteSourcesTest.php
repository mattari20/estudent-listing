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
            'source_retrieval_runs', 'source_monitoring_policies', 'assertions',
            'institutions', 'campuses', 'identity_match_candidates', 'identity_conflicts',
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

        $report = json_decode(Storage::disk('local')->get('private/source-qualification/test-report.json'), true, 512, JSON_THROW_ON_ERROR);
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
        $report = json_decode(Storage::disk('local')->get('private/source-qualification/non-html.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertSame(200, $report['results'][0]['http_status']);
        $this->assertNotNull($report['results'][0]['raw_sha256']);
        $this->assertSame(0, $report['results'][0]['extracted_count']);
        $this->assertSame('BLOCKED', $report['results'][0]['qualification_level']);
        $this->assertStringContainsString('Expected an HTML content type', $report['results'][0]['error']);
    }

    public function test_invalid_url_is_blocked_before_any_http_request(): void
    {
        config(['institute_source_qualification.test' => [
            ['id' => 'unsafe', 'url' => 'http://127.0.0.1/private', 'authority' => 'test authority', 'extractor' => 'unsupported-directory'],
        ]]);
        Http::fake();

        $exit = Artisan::call('institutes:qualify-official-sources', ['--stage' => 'test', '--delay-ms' => 0, '--output' => 'source-qualification/unsafe.json']);
        $report = json_decode(Storage::disk('local')->get('private/source-qualification/unsafe.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exit);
        $this->assertSame('BLOCKED', $report['results'][0]['qualification_level']);
        $this->assertStringContainsString('not publicly routable', $report['results'][0]['error']);
        Http::assertNothingSent();
    }
}

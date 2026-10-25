<?php

namespace Tests\Feature;

use App\Services\OfficialSourcePageVerifier;
use App\Services\SafeSourceHttpRetriever;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class VerifyOfferingGapSourcesTest extends TestCase
{
    public function test_source_verification_fetches_each_unique_url_once_and_reports_text_matches_without_database_writes(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'source-verification-');
        $this->assertNotFalse($path);
        $handle = fopen($path, 'wb');
        $this->assertNotFalse($handle);

        $url = 'https://university.example.edu/programmes';
        $body = '<html><head><title>Official Programmes</title></head><body>BS Computer Science Main Campus</body></html>';
        $response = new Response(new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/html; charset=UTF-8'], $body));
        $retriever = Mockery::mock(SafeSourceHttpRetriever::class);
        $retriever->shouldReceive('get')
            ->once()
            ->with($url, 15, 5, 'eStudent-OfficialSourceVerifier/1.0')
            ->andReturn($response);
        $this->app->instance(SafeSourceHttpRetriever::class, $retriever);

        try {
            fputcsv($handle, [
                'program_id', 'institution_id', 'program_name', 'official_source_url',
                'source_status', 'campus_status', 'remediation_decision',
                'candidate_campus_id', 'candidate_campus_name',
                'candidate_delivery_mode', 'campus_source_url',
            ]);
            fputcsv($handle, [
                10, 2, 'BS Computer Science', $url, 'source-found',
                'main-campus-confirmed', 'review', 4, 'Main Campus', 'on_campus', $url,
            ]);
            fclose($handle);

            $report = app(OfficialSourcePageVerifier::class)->verify($path);

            $this->assertSame('SOURCE_RETRIEVAL_REPORT_CREATED_REVIEW_REQUIRED', $report['verification_status']);
            $this->assertSame(1, $report['summary']['unique_source_urls']);
            $this->assertSame(1, $report['summary']['reachable_http_sources']);
            $this->assertSame(1, $report['summary']['programme_name_found_in_source']);
            $this->assertSame(1, $report['summary']['campus_name_found_in_source']);
            $this->assertTrue($report['records'][0]['program_name_found_in_source_text']);
            $this->assertTrue($report['records'][0]['campus_name_found_in_source_text']);
            $this->assertSame(hash('sha256', $body), $report['records'][0]['official_source_sha256']);
            $this->assertFalse($report['source_ownership_verified']);
            $this->assertSame(0, $report['database_mutations']);
            $this->assertFalse($report['mutation_performed']);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            @unlink($path);
        }
    }

    public function test_source_verification_reports_missing_urls_without_fetching_them(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'source-verification-');
        $this->assertNotFalse($path);
        $handle = fopen($path, 'wb');
        $this->assertNotFalse($handle);

        $retriever = Mockery::mock(SafeSourceHttpRetriever::class);
        $retriever->shouldNotReceive('get');
        $this->app->instance(SafeSourceHttpRetriever::class, $retriever);

        try {
            fputcsv($handle, [
                'program_id', 'institution_id', 'program_name', 'official_source_url',
                'source_status', 'campus_status', 'remediation_decision',
                'candidate_campus_id', 'candidate_campus_name',
                'candidate_delivery_mode', 'campus_source_url',
            ]);
            fputcsv($handle, [
                10, 2, 'BS Missing URL', '', 'not-verified',
                'verify-campus', 'blocked', 4, 'Main Campus', 'on_campus', '',
            ]);
            fclose($handle);

            $report = app(OfficialSourcePageVerifier::class)->verify($path);

            $this->assertSame(1, $report['summary']['missing_programme_urls']);
            $this->assertSame(1, $report['summary']['missing_campus_urls']);
            $this->assertSame('missing_url', $report['records'][0]['official_source_retrieval_status']);
            $this->assertSame('missing_url', $report['records'][0]['campus_source_retrieval_status']);
            $this->assertSame(0, $report['summary']['unique_source_urls']);
            $this->assertSame(0, $report['database_mutations']);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            @unlink($path);
        }
    }
}

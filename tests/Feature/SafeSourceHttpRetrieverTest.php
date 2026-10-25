<?php

namespace Tests\Feature;

use App\Models\Source;
use App\Services\SafeSourceHttpRetriever;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SafeSourceHttpRetrieverTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_to_private_ip_is_blocked_before_second_request(): void
    {
        Http::fake([
            'https://example.test/start' => Http::response('', 302, [
                'Location' => 'http://127.0.0.1/admin',
            ]),
        ]);

        $source = Source::create([
            'source_type' => 'web',
            'name' => 'Redirect to private network',
            'url' => 'https://example.test/start',
            'status' => 'active',
        ]);

        $run = app(\App\Services\SourceRetrievalService::class)->retrieve($source);

        $this->assertSame('failed', $run->status);
        $this->assertNull($run->snapshot_id);
        Http::assertSentCount(1);
    }

    public function test_relative_redirect_is_resolved_and_revalidated(): void
    {
        Http::fake([
            'https://example.test/start' => Http::response('', 302, [
                'Location' => '/final',
            ]),
            'https://example.test/final' => Http::response(
                'official content',
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $response = app(SafeSourceHttpRetriever::class)->get(
            'https://example.test/start',
            10,
            5,
            'eStudent-Test/1.0'
        );

        $this->assertTrue($response->successful());
        $this->assertSame('official content', $response->body());
        Http::assertSentCount(2);
    }

    public function test_embedded_url_credentials_are_rejected_before_request(): void
    {
        Http::fake();

        $source = Source::create([
            'source_type' => 'web',
            'name' => 'Credential-bearing source',
            'url' => 'https://user:secret@example.test/page',
            'status' => 'active',
        ]);

        $run = app(\App\Services\SourceRetrievalService::class)->retrieve($source);

        $this->assertSame('failed', $run->status);
        $this->assertNull($run->snapshot_id);
        Http::assertNothingSent();
    }

    public function test_https_redirect_cannot_downgrade_to_http(): void
    {
        Http::fake([
            'https://example.test/start' => Http::response('', 302, [
                'Location' => 'http://example.test/final',
            ]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTPS source redirects may not downgrade to HTTP.');

        app(SafeSourceHttpRetriever::class)->get(
            'https://example.test/start',
            10,
            5,
            'eStudent-Test/1.0'
        );
    }

    public function test_redirect_chain_is_limited_to_five_hops(): void
    {
        Http::fake([
            'https://example.test/*' => Http::response('', 302, [
                'Location' => '/loop',
            ]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Source exceeded the maximum of 5 redirects.');

        app(SafeSourceHttpRetriever::class)->get(
            'https://example.test/start',
            10,
            5,
            'eStudent-Test/1.0'
        );
    }

    public function test_redirect_without_location_is_rejected(): void
    {
        Http::fake([
            'https://example.test/start' => Http::response('', 302),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Source returned a redirect without a Location header.');

        app(SafeSourceHttpRetriever::class)->get(
            'https://example.test/start',
            10,
            5,
            'eStudent-Test/1.0'
        );
    }

}

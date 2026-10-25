<?php

namespace Tests\Feature;

use App\Services\SafeSourceHttpRetriever;
use App\Services\SourceUrlGuard;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SafeSourceQueryOnlyRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(SourceUrlGuard::class, new SourceUrlGuard(
            static fn (string $host, int $type): array => [['host' => $host, 'type' => 'A', 'ip' => '93.184.216.34']]
        ));
    }

    public function test_query_only_redirect_preserves_path_and_replaces_query(): void
    {
        Http::fake([
            'https://example.test/catalog?page=1' => Http::response('', 302, ['Location' => '?page=2']),
            'https://example.test/catalog?page=2' => Http::response('page two', 200, ['Content-Type' => 'text/html']),
        ]);

        $response = app(SafeSourceHttpRetriever::class)->get(
            'https://example.test/catalog?page=1',
            10,
            5,
            'eStudent-Test/1.0'
        );

        $this->assertTrue($response->successful());
        $this->assertSame('page two', $response->body());
        Http::assertSentCount(2);
    }
}

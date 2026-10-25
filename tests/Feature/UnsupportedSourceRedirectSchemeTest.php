<?php

namespace Tests\Feature;

use App\Services\SafeSourceHttpRetriever;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UnsupportedSourceRedirectSchemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(\App\Services\SourceUrlGuard::class, new \App\Services\SourceUrlGuard(
            static fn (string $host, int $type): array => [['host' => $host, 'type' => 'A', 'ip' => '93.184.216.34']]
        ));
    }

    public function test_redirect_to_unsupported_uri_scheme_is_rejected_before_following(): void
    {
        Http::fake([
            'https://example.test/start' => Http::response('', 302, [
                'Location' => 'ftp://127.0.0.1/private-file',
            ]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Source redirect Location uses an unsupported URI scheme.');

        try {
            app(SafeSourceHttpRetriever::class)->get(
                'https://example.test/start',
                10,
                5,
                'eStudent-Test/1.0'
            );
        } finally {
            Http::assertSentCount(1);
        }
    }
}

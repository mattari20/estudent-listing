<?php

namespace Tests\Feature;

use App\Services\SafeSourceHttpRetriever;
use App\Services\SourceUrlGuard;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SafeSourceProtocolRelativeRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(SourceUrlGuard::class, new SourceUrlGuard(
            static fn (string $host, int $type): array => [['host' => $host, 'type' => 'A', 'ip' => '93.184.216.34']]
        ));
    }

    public function test_protocol_relative_redirect_to_ipv4_loopback_is_blocked_before_second_request(): void
    {
        Http::fake([
            'https://example.test/start' => Http::response('', 302, ['Location' => '//127.0.0.1/admin']),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        try {
            app(SafeSourceHttpRetriever::class)->get('https://example.test/start', 10, 5, 'eStudent-Test/1.0');
        } finally {
            Http::assertSentCount(1);
        }
    }
}

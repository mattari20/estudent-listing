<?php

namespace Tests\Feature;

use App\Services\SourceUrlGuard;
use Tests\TestCase;

class SourceUrlGuardTest extends TestCase
{
    public function test_ipv6_loopback_literal_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('http://[::1]/internal');
    }

    public function test_ipv6_unique_local_literal_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('https://[fc00::1]/internal');
    }

    public function test_public_ipv6_literal_is_allowed_without_dns_lookup(): void
    {
        app(SourceUrlGuard::class)->validate('https://[2001:4860:4860::8888]/');
        $this->assertTrue(true);
    }

    public function test_embedded_url_credentials_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source URLs must not contain embedded credentials.');

        app(SourceUrlGuard::class)->validate('https://user:secret@example.com/path');
    }

    public function test_ipv4_mapped_ipv6_loopback_literal_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('http://[::ffff:127.0.0.1]/internal');
    }

    public function test_hexadecimal_ipv4_mapped_ipv6_loopback_literal_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('http://[::ffff:7f00:1]/internal');
    }

    public function test_ipv4_loopback_literal_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('http://127.0.0.1/internal');
    }
}

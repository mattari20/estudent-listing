<?php

namespace Tests\Feature;

use App\Services\SourceUrlGuard;
use Tests\TestCase;

class SourceUrlGuardIpv6CompatibleLoopbackTest extends TestCase
{
    public function test_ipv4_compatible_ipv6_loopback_literal_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('http://[::127.0.0.1]/internal');
    }
}

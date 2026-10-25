<?php

namespace Tests\Feature;

use App\Services\SourceUrlGuard;
use Tests\TestCase;

class SourceUrlGuardIpv6MulticastTest extends TestCase
{
    public function test_ipv6_multicast_literal_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('http://[ff02::1]/internal');
    }
}

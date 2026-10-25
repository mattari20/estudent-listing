<?php

namespace Tests\Feature;

use App\Services\SourceUrlGuard;
use Tests\TestCase;

class SourceUrlGuardReservedRangesTest extends TestCase
{
    public function test_ipv4_protocol_assignment_range_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('http://192.0.0.1/internal');
    }

    public function test_deprecated_ipv4_6to4_relay_anycast_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('http://192.88.99.1/internal');
    }

    public function test_ipv6_discard_only_range_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('http://[100::1]/internal');
    }

    public function test_ipv6_ietf_protocol_assignment_range_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('http://[2001::1]/internal');
    }

    public function test_ipv6_benchmarking_range_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('http://[2001:2::1]/internal');
    }
}

<?php

namespace Tests\Feature;

use App\Services\SourceUrlGuard;
use Tests\TestCase;

class SourceUrlGuardSpecialAddressRangesTest extends TestCase
{
    public function test_carrier_grade_nat_ipv4_address_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('http://100.64.0.1/internal');
    }

    public function test_ipv4_benchmarking_address_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('http://198.18.0.1/internal');
    }

    public function test_nat64_address_embedding_ipv4_loopback_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('http://[64:ff9b::7f00:1]/internal');
    }

    public function test_6to4_address_embedding_ipv4_loopback_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('http://[2002:7f00:1::]/internal');
    }

    public function test_deprecated_ipv6_site_local_address_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('http://[fec0::1]/internal');
    }
}

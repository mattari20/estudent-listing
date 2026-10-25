<?php

namespace Tests\Feature;

use App\Services\SourceUrlGuard;
use Tests\TestCase;

class SourceUrlGuardLeadingZeroIpv4LoopbackTest extends TestCase
{
    public function test_leading_zero_ipv4_loopback_literal_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(SourceUrlGuard::class)->validate('http://127.000.000.001/internal');
    }
}

<?php

namespace Tests\Feature;

use App\Services\SourceUrlGuard;
use Tests\TestCase;

class SourceUrlGuardHexIpv4LoopbackTest extends TestCase
{
    public function test_hex_ipv4_loopback_literal_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(SourceUrlGuard::class)->validate('http://0x7f000001/internal');
    }
}

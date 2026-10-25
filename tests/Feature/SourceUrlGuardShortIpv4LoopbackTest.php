<?php

namespace Tests\Feature;

use App\Services\SourceUrlGuard;
use Tests\TestCase;

class SourceUrlGuardShortIpv4LoopbackTest extends TestCase
{
    public function test_short_ipv4_loopback_literal_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(SourceUrlGuard::class)->validate('http://127.1/internal');
    }
}

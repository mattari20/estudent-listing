<?php

namespace Tests\Feature;

use App\Services\SourceUrlGuard;
use Tests\TestCase;

class SourceUrlGuardDecimalIpv4LoopbackTest extends TestCase
{
    public function test_decimal_ipv4_loopback_literal_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(SourceUrlGuard::class)->validate('http://2130706433/internal');
    }
}

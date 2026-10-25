<?php

namespace Tests\Feature;

use App\Services\SourceUrlGuard;
use Tests\TestCase;

class SourceUrlGuardOctalIpv4LoopbackTest extends TestCase
{
    public function test_octal_ipv4_loopback_literal_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(SourceUrlGuard::class)->validate('http://0177.0.0.1/internal');
    }
}

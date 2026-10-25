<?php

namespace Tests\Feature;

use App\Services\SourceUrlGuard;
use Tests\TestCase;

class SourceUrlGuardIpv6DocumentationTest extends TestCase
{
    public function test_ipv6_documentation_literal_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host is not publicly routable.');

        app(SourceUrlGuard::class)->validate('https://[2001:db8::1]/example');
    }
}

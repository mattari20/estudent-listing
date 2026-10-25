<?php

namespace Tests\Feature;

use App\Services\SourceUrlGuard;
use Tests\TestCase;

class SourceUrlGuardMixedDnsAnswerTest extends TestCase
{
    public function test_hostname_with_public_and_private_dns_answers_is_rejected(): void
    {
        $guard = new SourceUrlGuard(static fn (string $host, int $type): array => [
            ['host' => $host, 'type' => 'A', 'ip' => '93.184.216.34'],
            ['host' => $host, 'type' => 'AAAA', 'ipv6' => '::1'],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host resolves to a non-public address.');

        $guard->validate('https://mixed-answer.example.test/source');
    }
}

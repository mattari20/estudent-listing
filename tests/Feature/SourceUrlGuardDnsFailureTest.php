<?php

namespace Tests\Feature;

use App\Services\SourceUrlGuard;
use Tests\TestCase;

class SourceUrlGuardDnsFailureTest extends TestCase
{
    public function test_empty_dns_answer_is_rejected(): void
    {
        $guard = new SourceUrlGuard(static fn (string $host, int $type): array => []);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host did not resolve to a public IP address.');

        $guard->validate('https://no-address.example.test/path');
    }

    public function test_failed_dns_lookup_is_rejected(): void
    {
        $guard = new SourceUrlGuard(static fn (string $host, int $type): false => false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host did not resolve to a public IP address.');

        $guard->validate('https://dns-failure.example.test/path');
    }

    public function test_mixed_public_and_private_dns_answers_are_rejected(): void
    {
        $guard = new SourceUrlGuard(static fn (string $host, int $type): array => [
            ['host' => $host, 'type' => 'A', 'ip' => '93.184.216.34'],
            ['host' => $host, 'type' => 'A', 'ip' => '127.0.0.1'],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host resolves to a non-public address.');

        $guard->validate('https://mixed-answer.example.test/path');
    }

    public function test_public_dns_answer_is_allowed(): void
    {
        $guard = new SourceUrlGuard(static fn (string $host, int $type): array => [
            ['host' => $host, 'type' => 'A', 'ip' => '93.184.216.34'],
        ]);

        $guard->validate('https://public-answer.example.test/path');
        $this->assertTrue(true);
    }
}

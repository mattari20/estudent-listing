<?php

namespace Tests\Feature;

use App\Services\SourceUrlGuard;
use Tests\TestCase;

class SourceUrlGuardEmptyDnsAnswerTest extends TestCase
{
    public function test_empty_dns_answer_is_rejected(): void
    {
        $guard = new SourceUrlGuard(static fn (string $host, int $type): array => []);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source host did not resolve to a public IP address.');

        $guard->validate('https://empty-answer.example.test/source');
    }
}

<?php

namespace Tests\Feature;

use App\Services\SourceUrlGuard;
use RuntimeException;
use Tests\TestCase;

class SourceUrlGuardResolverExceptionTest extends TestCase
{
    public function test_dns_resolver_exception_is_wrapped_as_validation_failure(): void
    {
        $guard = new SourceUrlGuard(static function (string $host, int $type): array {
            throw new RuntimeException('resolver unavailable');
        });

        try {
            $guard->validate('https://resolver-error.example.test/source');
            $this->fail('Expected DNS resolution failure to block the URL.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame('Source host DNS resolution failed.', $exception->getMessage());
            $this->assertInstanceOf(RuntimeException::class, $exception->getPrevious());
        }
    }
}

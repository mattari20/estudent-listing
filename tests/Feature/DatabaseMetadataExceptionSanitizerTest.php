<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Database\DatabaseMetadataExceptionSanitizer;
use PDOException;
use RuntimeException;
use Tests\TestCase;

class DatabaseMetadataExceptionSanitizerTest extends TestCase
{
    public function test_pdo_metadata_exception_exposes_safe_codes_and_redacts_credentials(): void
    {
        $exception = new PDOException(
            'Connection failed for db.internal user ci_user password=inline-secret with token-secret',
            '23000'
        );
        $exception->errorInfo = ['23000', 1062, 'duplicate key'];

        $details = DatabaseMetadataExceptionSanitizer::describe(
            $exception,
            ['db.internal', 'ci_user', 'token-secret']
        );

        $this->assertSame(PDOException::class, $details['exception_class']);
        $this->assertSame('23000', $details['sqlstate']);
        $this->assertSame('1062', $details['vendor_code']);
        $this->assertStringContainsString('[REDACTED]', $details['sanitized_message']);
        $this->assertStringNotContainsString('db.internal', $details['sanitized_message']);
        $this->assertStringNotContainsString('ci_user', $details['sanitized_message']);
        $this->assertStringNotContainsString('inline-secret', $details['sanitized_message']);
        $this->assertStringNotContainsString('token-secret', $details['sanitized_message']);
    }

    public function test_non_pdo_exception_uses_safe_fallback_codes_and_redacts_password_assignments(): void
    {
        $exception = new RuntimeException('Metadata lookup failed; pwd=plain-secret');

        $details = DatabaseMetadataExceptionSanitizer::describe($exception, []);

        $this->assertSame(RuntimeException::class, $details['exception_class']);
        $this->assertSame('0', $details['sqlstate']);
        $this->assertSame('unavailable', $details['vendor_code']);
        $this->assertStringContainsString('pwd=[REDACTED]', $details['sanitized_message']);
        $this->assertStringNotContainsString('plain-secret', $details['sanitized_message']);
    }
}

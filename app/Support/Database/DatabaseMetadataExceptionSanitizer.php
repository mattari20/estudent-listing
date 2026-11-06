<?php

declare(strict_types=1);

namespace App\Support\Database;

use PDOException;
use Throwable;

final class DatabaseMetadataExceptionSanitizer
{
    /**
     * @param list<string> $sensitiveValues
     * @return array{exception_class: string, sqlstate: string, vendor_code: string, sanitized_message: string}
     */
    public static function describe(Throwable $exception, array $sensitiveValues): array
    {
        $errorInfo = $exception instanceof PDOException && is_array($exception->errorInfo ?? null)
            ? $exception->errorInfo
            : [];
        $sqlState = (string) ($errorInfo[0] ?? $exception->getCode());
        $vendorCode = isset($errorInfo[1]) ? (string) $errorInfo[1] : 'unavailable';
        $message = $exception->getMessage();

        foreach ($sensitiveValues as $sensitive) {
            if ($sensitive !== '') {
                $message = str_replace($sensitive, '[REDACTED]', $message);
            }
        }

        $message = preg_replace('/(password|passwd|pwd)\s*=\s*[^\s;]+/i', '$1=[REDACTED]', $message)
            ?? '[message sanitization failed]';

        return [
            'exception_class' => get_class($exception),
            'sqlstate' => $sqlState,
            'vendor_code' => $vendorCode,
            'sanitized_message' => $message,
        ];
    }
}

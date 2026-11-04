<?php

namespace App\Models\Concerns;

use RuntimeException;

trait PreventsAuditMutation
{
    protected static function bootPreventsAuditMutation(): void
    {
        static::updating(function (): never {
            throw new RuntimeException('Audit history is append-only; record a new compensating event instead.');
        });

        static::deleting(function (): never {
            throw new RuntimeException('Audit history is append-only and cannot be deleted.');
        });
    }
}

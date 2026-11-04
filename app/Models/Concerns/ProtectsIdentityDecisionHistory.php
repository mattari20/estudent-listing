<?php

namespace App\Models\Concerns;

use RuntimeException;

trait ProtectsIdentityDecisionHistory
{
    protected static function bootProtectsIdentityDecisionHistory(): void
    {
        static::updating(function ($model): void {
            $dirty = array_keys($model->getDirty());
            $otherChanges = array_diff($dirty, ['decision_identity_key', 'updated_at']);
            $isOneTimeKeyEnrichment = $model->getRawOriginal('decision_identity_key') === null
                && $model->getAttribute('decision_identity_key') !== null
                && $otherChanges === [];

            if (! $isOneTimeKeyEnrichment) {
                throw new RuntimeException('Identity decision history is immutable; only a one-time legacy idempotency-key enrichment is permitted.');
            }
        });

        static::deleting(function (): never {
            throw new RuntimeException('Identity decision history cannot be deleted.');
        });
    }
}

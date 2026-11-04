<?php

namespace App\Services;

use App\Models\Source;
use App\Models\SourceSnapshot;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class SourceSnapshotService
{
    public function capture(
        Source $source,
        string $content,
        ?string $reference = null,
        ?string $contentType = null,
        array $metadata = [],
        ?string $captureKey = null,
        ?DateTimeInterface $capturedAt = null,
    ): SourceSnapshot {
        if ($captureKey !== null && ($captureKey === '' || strlen($captureKey) > 64)) {
            throw new \InvalidArgumentException('Snapshot capture keys must contain between 1 and 64 characters.');
        }

        $attributes = [
            'source_id' => $source->id,
            'reference' => $reference,
            'content_hash' => hash('sha256', $content),
            'content_length' => strlen($content),
            'content_type' => $contentType,
            'content' => $content,
            'captured_at' => $capturedAt ?? now(),
            'status' => 'captured',
            'metadata' => $metadata,
        ];

        return DB::transaction(function () use ($source, $captureKey, $attributes): SourceSnapshot {
            // Every supported capture path takes the same per-source lock used by
            // comparison. This makes snapshot insertion and predecessor evaluation
            // serializable for one source without globally serializing other sources.
            Source::query()->lockForUpdate()->findOrFail($source->id);

            if ($captureKey === null) {
                return SourceSnapshot::create($attributes);
            }

            $snapshot = SourceSnapshot::query()->firstOrCreate(
                ['capture_key' => $captureKey],
                $attributes
            );

            if ((int) $snapshot->source_id !== (int) $source->id
                || ! hash_equals((string) $snapshot->content_hash, (string) $attributes['content_hash'])) {
                throw new \LogicException('The snapshot capture key is already assigned to a different source or content payload.');
            }

            return $snapshot;
        }, 3);
    }

    public function latest(Source $source): ?SourceSnapshot
    {
        return SourceSnapshot::query()
            ->where('source_id', $source->id)
            ->orderByDesc('captured_at')
            ->orderByDesc('id')
            ->first();
    }
}

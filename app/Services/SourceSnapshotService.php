<?php

namespace App\Services;

use App\Models\Source;
use App\Models\SourceSnapshot;
use Illuminate\Database\Eloquent\Model;

class SourceSnapshotService
{
    public function capture(
        Source $source,
        string $content,
        ?string $reference = null,
        ?string $contentType = null,
        array $metadata = [],
        ?string $captureKey = null,
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
            'captured_at' => now(),
            'status' => 'captured',
            'metadata' => $metadata,
        ];

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
    }

    public function latest(Source $source): ?SourceSnapshot
    {
        return SourceSnapshot::where('source_id', $source->id)->latest('captured_at')->first();
    }
}

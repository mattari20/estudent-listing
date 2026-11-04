<?php

namespace App\Services;

use App\Models\SourceRetrievalRun;
use App\Models\SourceSnapshot;
use DateTimeInterface;
use RuntimeException;

class SourceRetrievalRunFinalizer
{
    public function complete(
        SourceRetrievalRun $run,
        SourceSnapshot $snapshot,
        int $httpStatus,
        ?string $contentType,
        int $contentLength,
        DateTimeInterface $completedAt,
    ): SourceRetrievalRun {
        $updated = $run->update([
            'status' => 'completed',
            'http_status' => $httpStatus,
            'content_type' => $contentType,
            'content_length' => $contentLength,
            'content_hash' => $snapshot->content_hash,
            'snapshot_id' => $snapshot->id,
            'completed_at' => $completedAt,
            'error_message' => null,
        ]);

        if (! $updated) {
            throw new RuntimeException('Source retrieval completion was not persisted.');
        }

        return $run->refresh();
    }
}

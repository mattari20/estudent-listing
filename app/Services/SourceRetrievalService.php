<?php

namespace App\Services;

use App\Models\Source;
use App\Models\SourceRetrievalRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SourceRetrievalService
{
    public function retrieve(Source $source, ?string $idempotencyKey = null): SourceRetrievalRun
    {
        if ($source->status !== 'active') {
            throw new \InvalidArgumentException('Source retrieval is only allowed for active sources.');
        }

        if (! $source->url) {
            throw new \InvalidArgumentException('Source has no URL.');
        }

        $idempotencyKey ??= (string) Str::uuid();

        if ($idempotencyKey === '' || strlen($idempotencyKey) > 64) {
            throw new \InvalidArgumentException('Source retrieval idempotency keys must contain between 1 and 64 characters.');
        }

        [$run, $shouldRetrieve] = DB::transaction(function () use ($source, $idempotencyKey): array {
            $run = SourceRetrievalRun::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($run && (int) $run->source_id !== (int) $source->id) {
                throw new \InvalidArgumentException('The source retrieval idempotency key is already assigned to another source.');
            }

            if (! $run) {
                $run = SourceRetrievalRun::create([
                    'source_id' => $source->id,
                    'idempotency_key' => $idempotencyKey,
                    'status' => 'started',
                    'started_at' => now(),
                ]);

                return [$run, true];
            }

            if ($run->status === 'completed') {
                return [$run, false];
            }

            $isFreshInProgress = $run->status === 'started'
                && $run->started_at?->greaterThan(now()->subMinutes(15));

            if ($isFreshInProgress) {
                return [$run, false];
            }

            $run->update([
                'status' => 'started',
                'http_status' => null,
                'content_type' => null,
                'content_length' => null,
                'content_hash' => null,
                'snapshot_id' => null,
                'error_message' => null,
                'started_at' => now(),
                'completed_at' => null,
            ]);

            return [$run->refresh(), true];
        });

        if (! $shouldRetrieve) {
            return $run;
        }

        try {
            $response = app(SafeSourceHttpRetriever::class)->get(
                $source->url,
                20,
                10,
                'eStudent-SourceMonitor/1.0'
            );
        } catch (Throwable $exception) {
            return $this->markFailed($run, null, null, 'retrieval_exception:'.mb_substr(get_class($exception), 0, 120));
        }

        $httpStatus = $response->status();
        $contentType = $response->header('Content-Type');
        $body = $response->body();

        if (! $response->successful()) {
            return $this->markFailed($run, $httpStatus, $contentType, 'HTTP '.$httpStatus);
        }

        if (strlen($body) > 5 * 1024 * 1024) {
            return $this->markFailed($run, $httpStatus, $contentType, 'response_too_large');
        }

        // captured_at is the authoritative time at which the successful response
        // body became an observed snapshot. Persist it once and reuse it when a
        // retry finds the existing capture key.
        $capturedAt = now();
        $snapshot = app(SourceSnapshotService::class)->capture(
            $source,
            $body,
            $source->url,
            $contentType,
            $response->headers(),
            hash('sha256', 'retrieval-snapshot:v1|'.$run->id),
            $capturedAt,
        );

        return app(SourceRetrievalRunFinalizer::class)->complete(
            $run,
            $snapshot,
            $httpStatus,
            $contentType,
            strlen($body),
            $snapshot->captured_at ?? $capturedAt,
        );
    }

    private function markFailed(
        SourceRetrievalRun $run,
        ?int $httpStatus,
        ?string $contentType,
        string $safeFailureCode
    ): SourceRetrievalRun {
        $run->update([
            'status' => 'failed',
            'http_status' => $httpStatus,
            'content_type' => $contentType,
            'error_message' => mb_substr($safeFailureCode, 0, 160),
            'completed_at' => now(),
        ]);

        return $run->refresh();
    }
}

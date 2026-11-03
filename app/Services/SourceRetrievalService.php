<?php

namespace App\Services;

use App\Models\Source;
use App\Models\SourceRetrievalRun;
use Illuminate\Support\Facades\DB;

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

        $idempotencyKey ??= hash(
            'sha256',
            'source-retrieval:v1|'.$source->id.'|'.now()->utc()->startOfHour()->toIso8601String()
        );

        // The unique key protects retries of the same source/hour slot. The
        // governed execution lease serializes pipeline runners; this row lock
        // also prevents a second caller from taking over a live in-progress run.
        [$run, $shouldRetrieve] = DB::transaction(function () use ($source, $idempotencyKey): array {
            $run = SourceRetrievalRun::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

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

        $httpStatus = null;
        $contentType = null;

        try {
            $response = app(SafeSourceHttpRetriever::class)->get(
                $source->url,
                20,
                10,
                'eStudent-SourceMonitor/1.0'
            );

            $httpStatus = $response->status();
            $contentType = $response->header('Content-Type');
            $body = $response->body();

            if (! $response->successful()) {
                throw new \RuntimeException('Source returned HTTP '.$response->status().'.');
            }

            if (strlen($body) > 5 * 1024 * 1024) {
                throw new \RuntimeException('Source response exceeds the 5 MB retrieval limit.');
            }

            $snapshot = app(SourceSnapshotService::class)->capture(
                $source,
                $body,
                $source->url,
                $response->header('Content-Type'),
                $response->headers(),
            );

            $run->update([
                'status' => 'completed',
                'http_status' => $response->status(),
                'content_type' => $response->header('Content-Type'),
                'content_length' => strlen($body),
                'content_hash' => $snapshot->content_hash,
                'snapshot_id' => $snapshot->id,
                'completed_at' => now(),
            ]);

            return $run->refresh();
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'http_status' => $httpStatus,
                'content_type' => $contentType,
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
                'completed_at' => now(),
            ]);

            return $run->refresh();
        }
    }
}

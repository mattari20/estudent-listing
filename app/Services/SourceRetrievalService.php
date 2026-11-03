<?php

namespace App\Services;

use App\Models\Source;
use App\Models\SourceRetrievalRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        // A new retrieval call is a new operation by default. Callers that
        // retry the same logical operation must supply the same explicit key.
        $idempotencyKey ??= (string) Str::uuid();

        if ($idempotencyKey === '' || strlen($idempotencyKey) > 64) {
            throw new \InvalidArgumentException('Source retrieval idempotency keys must contain between 1 and 64 characters.');
        }

        // The unique key protects retries of the same source/hour slot. The
        // governed execution lease serializes pipeline runners; this row lock
        // also prevents a second caller from taking over a live in-progress run.
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
                hash('sha256', 'retrieval-snapshot:v1|'.$run->id),
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

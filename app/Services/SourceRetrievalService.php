<?php

namespace App\Services;

use App\Models\Source;
use App\Models\SourceRetrievalRun;
use Illuminate\Support\Facades\Http;

class SourceRetrievalService
{
    public function retrieve(Source $source): SourceRetrievalRun
    {
        $run = SourceRetrievalRun::create([
            'source_id' => $source->id,
            'status' => 'started',
            'started_at' => now(),
        ]);

        try {
            if (! $source->url) {
                throw new \InvalidArgumentException('Source has no URL.');
            }

            app(SourceUrlGuard::class)->validate($source->url);

            $response = Http::timeout(20)
                ->connectTimeout(10)
                ->withHeaders(['User-Agent' => 'eStudent-SourceMonitor/1.0'])
                ->get($source->url);

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
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
                'completed_at' => now(),
            ]);

            return $run->refresh();
        }
    }
}

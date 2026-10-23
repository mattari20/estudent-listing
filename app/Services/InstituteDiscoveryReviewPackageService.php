<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\ImportBatch;
use App\Models\SourceSnapshot;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class InstituteDiscoveryReviewPackageService
{
    public function create(ImportBatch $batch, ?SourceSnapshot $snapshotOverride = null, ?int $evidenceIdOverride = null): array
    {
        if (! in_array($batch->source_type, ['automated_discovery', 'ai_extraction'], true)) {
            throw new RuntimeException('Review packages can only be created for discovery batches.');
        }

        $batch->loadMissing(['source', 'rows']);
        $firstPayload = $batch->rows->first()?->payload ?? [];
        $provenance = is_array($firstPayload) ? ($firstPayload['_provenance'] ?? []) : [];
        $snapshotId = (int) ($snapshotOverride?->id ?? ($provenance['snapshot_id'] ?? 0));
        $evidenceId = $evidenceIdOverride ?? (isset($provenance['evidence_id']) ? (int) $provenance['evidence_id'] : null);

        // Empty discovery batches have no rows from which to read provenance.
        // Recover the snapshot ID from the batch's recorded no-change note so the
        // protected CLI command can retrieve/rebuild the already-private package.
        if ($snapshotId < 1 && preg_match('/source snapshot #(\\d+)/i', (string) $batch->notes, $matches) === 1) {
            $snapshotId = (int) $matches[1];
        }

        if ($snapshotId < 1) {
            throw new RuntimeException('The discovery batch has no source snapshot provenance.');
        }

        $snapshot = $snapshotOverride ?? SourceSnapshot::query()->find($snapshotId);
        if (! $snapshot || (int) $snapshot->source_id !== (int) $batch->source_id) {
            throw new RuntimeException('The discovery batch snapshot does not match its source.');
        }

        if ($evidenceId === null) {
            $evidenceId = Evidence::query()
                ->where('source_id', $batch->source_id)
                ->where('snapshot_id', $snapshotId)
                ->orderByDesc('id')
                ->value('id');
            $evidenceId = $evidenceId === null ? null : (int) $evidenceId;
        }

        $directory = 'institute-discovery-review-packages/snapshot-'.$snapshotId;
        $csvPath = $directory.'/candidates.csv';
        $manifestPath = $directory.'/manifest.json';
        $disk = Storage::disk('local');

        if ($disk->exists($manifestPath) && $disk->exists($csvPath)) {
            $existing = json_decode($disk->get($manifestPath), true) ?: [];

            return [
                'status' => 'reused',
                'batch_id' => (int) $batch->id,
                'snapshot_id' => $snapshotId,
                'candidate_count' => (int) ($existing['candidate_count'] ?? $batch->rows->count()),
                'csv_path' => $csvPath,
                'manifest_path' => $manifestPath,
            ];
        }

        $headers = [
            'batch_id', 'row_id', 'row_number', 'entity_type', 'institution_id',
            'candidate_name', 'candidate_slug', 'official_source_url', 'snapshot_id',
            'evidence_id', 'observed_at', 'confidence', 'extraction_method',
            'duplicate_state', 'validation_errors', 'review_status',
        ];

        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            throw new RuntimeException('Unable to create a temporary review package stream.');
        }

        fputcsv($stream, $headers);
        foreach ($batch->rows->sortBy('row_number') as $row) {
            $payload = is_array($row->payload) ? $row->payload : [];
            $meta = is_array($payload['_provenance'] ?? null) ? $payload['_provenance'] : [];
            fputcsv($stream, [
                $batch->id,
                $row->id,
                $row->row_number,
                $batch->entity_type,
                $row->parent_subject_id,
                $payload['name'] ?? '',
                $payload['slug'] ?? '',
                $batch->source?->url ?? '',
                $snapshotId,
                $meta['evidence_id'] ?? '',
                $snapshot->captured_at?->toIso8601String() ?? '',
                $meta['confidence'] ?? '',
                $meta['method'] ?? $batch->source_type,
                $row->match_state ?? '',
                json_encode($row->validation_errors ?? [], JSON_UNESCAPED_SLASHES),
                $row->status ?? '',
            ]);
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        if (! is_string($csv)) {
            throw new RuntimeException('Unable to read the generated review package.');
        }

        $candidateCount = $batch->rows->count();
        $manifest = [
            'package_type' => 'institute_discovery_review',
            'schema_version' => 1,
            'status' => $candidateCount > 0 ? 'review_required' : 'no_candidates',
            'created_at' => now()->toIso8601String(),
            'batch_id' => (int) $batch->id,
            'batch_key' => $batch->batch_key,
            'entity_type' => $batch->entity_type,
            'source_type' => $batch->source_type,
            'source_id' => (int) $batch->source_id,
            'official_source_url' => $batch->source?->url,
            'snapshot_id' => $snapshotId,
            'evidence_id' => $evidenceId,
            'snapshot_hash' => $snapshot->content_hash,
            'observed_at' => $snapshot->captured_at?->toIso8601String(),
            'candidate_count' => $candidateCount,
            'duplicate_count' => $batch->rows->where('match_state', 'duplicate')->count(),
            'validation_error_count' => $batch->rows->filter(fn ($row) => ! empty($row->validation_errors))->count(),
            'canonical_mutations_performed' => false,
            'automatic_approval_performed' => false,
            'files' => ['csv' => 'candidates.csv'],
        ];

        $disk->makeDirectory($directory);
        if (! $disk->put($csvPath, $csv) ||
            ! $disk->put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
            throw new RuntimeException('Unable to persist the discovery review package.');
        }

        return [
            'status' => 'created',
            'batch_id' => (int) $batch->id,
            'snapshot_id' => $snapshotId,
            'candidate_count' => $candidateCount,
            'csv_path' => $csvPath,
            'manifest_path' => $manifestPath,
        ];
    }
}

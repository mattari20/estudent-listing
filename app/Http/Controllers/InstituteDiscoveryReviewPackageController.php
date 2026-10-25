<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InstituteDiscoveryReviewPackageController
{
    public function download(Request $request, ImportBatch $importBatch, string $file): BinaryFileResponse
    {
        abort_unless(in_array($importBatch->source_type, ['automated_discovery', 'ai_extraction'], true), 404);
        abort_unless(in_array($file, ['csv', 'manifest'], true), 404);

        $importBatch->loadMissing('rows');
        $firstPayload = $importBatch->rows->first()?->payload ?? [];
        $provenance = is_array($firstPayload) ? ($firstPayload['_provenance'] ?? []) : [];
        $snapshotId = (int) ($provenance['snapshot_id'] ?? 0);

        // Empty/no-change discovery batches have no row payload to carry provenance.
        // Recover the snapshot from the service's recorded batch note, as the package
        // service does, so their manifest/CSV remain available to operators.
        if ($snapshotId < 1 && preg_match('/source snapshot #(\d+)/i', (string) $importBatch->notes, $matches) === 1) {
            $snapshotId = (int) $matches[1];
        }

        abort_if($snapshotId < 1, 404);

        $disk = Storage::disk('local');
        $filename = $file === 'csv' ? 'candidates.csv' : 'manifest.json';
        $directories = [
            'institute-discovery-review-packages/batch-'.$importBatch->id.'-snapshot-'.$snapshotId,
            // Backward compatibility for packages generated before batch-scoped paths.
            'institute-discovery-review-packages/snapshot-'.$snapshotId,
        ];
        $path = null;

        foreach ($directories as $directory) {
            $candidateManifestPath = $directory.'/manifest.json';
            $candidateCsvPath = $directory.'/candidates.csv';
            if (! $disk->exists($candidateManifestPath) || ! $disk->exists($candidateCsvPath)) {
                continue;
            }

            $manifest = json_decode($disk->get($candidateManifestPath), true);
            if (! is_array($manifest)
                || (int) ($manifest['batch_id'] ?? 0) !== (int) $importBatch->id
                || (int) ($manifest['snapshot_id'] ?? 0) !== $snapshotId) {
                continue;
            }

            $expectedChecksum = data_get($manifest, 'checksums.candidates_csv_sha256');
            if (! is_string($expectedChecksum)
                || ! hash_equals($expectedChecksum, hash('sha256', $disk->get($candidateCsvPath)))) {
                continue;
            }

            $path = $directory.'/'.$filename;
            break;
        }

        abort_unless(is_string($path) && $disk->exists($path), 404);

        $response = response()->download($disk->path($path), $filename, [
            'X-Content-Type-Options' => 'nosniff',
        ]);

        // Symfony's file-download response may add a public cache directive by default.
        // Override it after construction so private review data is never cacheable by shared caches.
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }
}

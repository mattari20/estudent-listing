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

        $directory = 'institute-discovery-review-packages/snapshot-'.$snapshotId;
        $filename = $file === 'csv' ? 'candidates.csv' : 'manifest.json';
        $path = $directory.'/'.$filename;
        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        $response = response()->download($disk->path($path), $filename, [
            'X-Content-Type-Options' => 'nosniff',
        ]);

        // Symfony's file-download response may add a public cache directive by default.
        // Override it after construction so private review data is never cacheable by shared caches.
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }
}

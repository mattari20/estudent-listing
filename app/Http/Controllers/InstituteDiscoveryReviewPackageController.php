<?php

namespace App\\Http\\Controllers;

use App\\Models\\ImportBatch;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Storage;
use Symfony\\Component\\HttpFoundation\\BinaryFileResponse;

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
        abort_if($snapshotId < 1, 404);

        $directory = 'institute-discovery-review-packages/snapshot-'.$snapshotId;
        $filename = $file === 'csv' ? 'candidates.csv' : 'manifest.json';
        $path = $directory.'/'.$filename;
        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        return response()->download($disk->path($path), $filename, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

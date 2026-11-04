<?php

namespace App\Services;

use App\Models\ImportBatch;
use App\Models\SourceSnapshot;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class InstituteDiscoveryReviewPackageService
{
    private const ROOT = 'institute-discovery-review-packages';
    private const PACKAGE_TYPE = 'institute_discovery_review';
    private const SCHEMA_VERSION = 2;

    public function create(ImportBatch $batch, ?SourceSnapshot $snapshotOverride = null, ?int $evidenceIdOverride = null): array
    {
        if (! in_array($batch->source_type, ['automated_discovery', 'ai_extraction'], true)) {
            throw new RuntimeException('Review packages can only be created for discovery batches.');
        }
        if ($batch->status !== 'validated' || $batch->completed_at === null) {
            throw new RuntimeException('An incomplete discovery batch cannot receive a ready review package.');
        }

        $batch->loadMissing(['source', 'rows']);
        $firstPayload = $batch->rows->first()?->payload ?? [];
        $provenance = is_array($firstPayload) ? ($firstPayload['_provenance'] ?? []) : [];
        $snapshotId = (int) ($snapshotOverride?->id ?? ($provenance['snapshot_id'] ?? 0));
        if ($snapshotId < 1 && preg_match('/source snapshot #([1-9][0-9]*)/i', (string) $batch->notes, $matches) === 1) {
            $snapshotId = (int) $matches[1];
        }
        if ($snapshotId < 1) {
            throw new RuntimeException('The discovery batch has no source snapshot provenance.');
        }

        $snapshot = $snapshotOverride ?? SourceSnapshot::query()->find($snapshotId);
        if (! $snapshot || (int) $snapshot->id !== $snapshotId || (int) $snapshot->source_id !== (int) $batch->source_id) {
            throw new RuntimeException('The discovery batch snapshot does not match its source.');
        }
        if (! $batch->source) {
            throw new RuntimeException('The discovery batch source is missing.');
        }

        $evidenceId = $evidenceIdOverride ?? (isset($provenance['evidence_id']) ? (int) $provenance['evidence_id'] : null);
        $directory = self::ROOT.'/batch-'.$batch->id.'-snapshot-'.$snapshotId;
        $csvPath = $directory.'/candidates.csv';
        $manifestPath = $directory.'/manifest.json';
        $disk = Storage::disk('local');

        return $this->withPackageLock($batch->id, $snapshotId, function () use (
            $disk, $batch, $snapshot, $snapshotId, $evidenceId, $directory, $csvPath, $manifestPath
        ): array {
            $this->ensureDirectory($disk, $directory);
            $existing = $this->readValidPackage($disk, $directory, $batch, $snapshot, $snapshotId);
            if ($existing !== null) {
                return [
                    'status' => 'reused',
                    'batch_id' => (int) $batch->id,
                    'snapshot_id' => $snapshotId,
                    'candidate_count' => (int) $existing['candidate_count'],
                    'csv_path' => $csvPath,
                    'manifest_path' => $manifestPath,
                ];
            }

            $this->assertKnownDirectoryContents($disk, $directory);
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
                $values = [
                    $batch->id, $row->id, $row->row_number, $batch->entity_type, $row->parent_subject_id,
                    $payload['name'] ?? '', $payload['slug'] ?? '', $batch->source?->url ?? '',
                    $snapshotId, $meta['evidence_id'] ?? '', $snapshot->captured_at?->toIso8601String() ?? '',
                    $meta['confidence'] ?? '', $meta['method'] ?? $batch->source_type,
                    $row->match_state ?? '', json_encode($row->validation_errors ?? [], JSON_UNESCAPED_SLASHES),
                    $row->status ?? '',
                ];
                fputcsv($stream, array_map($this->spreadsheetSafeCell(...), $values));
            }
            rewind($stream);
            $csv = stream_get_contents($stream);
            fclose($stream);
            if (! is_string($csv)) {
                throw new RuntimeException('Unable to read the generated review package.');
            }

            $candidateCount = $batch->rows->count();
            $manifest = [
                'package_type' => self::PACKAGE_TYPE,
                'schema_version' => self::SCHEMA_VERSION,
                'status' => $candidateCount > 0 ? 'review_required' : 'no_candidates',
                'created_at' => now()->toIso8601String(),
                'batch_id' => (int) $batch->id,
                'batch_key' => (string) $batch->batch_key,
                'entity_type' => (string) $batch->entity_type,
                'source_type' => (string) $batch->source_type,
                'source_id' => (int) $batch->source_id,
                'official_source_url' => $batch->source?->url,
                'snapshot_id' => $snapshotId,
                'evidence_id' => $evidenceId,
                'snapshot_hash' => (string) $snapshot->content_hash,
                'observed_at' => $snapshot->captured_at?->toIso8601String(),
                'candidate_count' => $candidateCount,
                'extracted_count' => $candidateCount,
                'new_candidate_count' => $batch->rows->where('status', 'candidate')->count(),
                'duplicate_count' => $batch->rows->where('match_state', 'duplicate')->count(),
                'validation_error_count' => $batch->rows->filter(fn ($row) => ! empty($row->validation_errors))->count(),
                'canonical_mutations_performed' => false,
                'automatic_approval_performed' => false,
                'files' => ['csv' => 'candidates.csv'],
                'checksums' => ['candidates_csv_sha256' => hash('sha256', $csv)],
            ];
            $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

            // Each artifact is atomically replaced on the same filesystem. The manifest
            // is the commit marker and is always installed last; readers fail closed
            // while a pair is incomplete or being replaced.
            $this->atomicWrite($disk, $directory, 'candidates.csv', $csv);
            $this->atomicWrite($disk, $directory, 'manifest.json', $manifestJson);

            $verified = $this->readValidPackage($disk, $directory, $batch, $snapshot, $snapshotId);
            if ($verified === null) {
                throw new RuntimeException('The generated review package failed post-write verification.');
            }

            return [
                'status' => 'created',
                'batch_id' => (int) $batch->id,
                'snapshot_id' => $snapshotId,
                'candidate_count' => $candidateCount,
                'csv_path' => $csvPath,
                'manifest_path' => $manifestPath,
            ];
        });
    }

    /**
     * Reconcile only old application-owned artifacts whose database batch is absent.
     * Existing batches are always preserved; ambiguous directories are never deleted.
     */
    public function reconcileOrphans(int $minimumAgeHours, bool $apply = false): array
    {
        if ($minimumAgeHours < 24) {
            throw new RuntimeException('The minimum artifact age must be at least 24 hours.');
        }
        $disk = Storage::disk('local');
        $root = $this->rootPath($disk);
        if (! is_dir($root)) {
            return ['candidates' => [], 'skipped' => [], 'deleted' => []];
        }
        $this->assertNoSymlink($root);
        $cutoff = now()->subHours($minimumAgeHours)->timestamp;
        $candidates = [];
        $skipped = [];
        $deleted = [];

        foreach (scandir($root) ?: [] as $name) {
            if ($name === '.' || $name === '..' || $name === '.locks') {
                continue;
            }
            if (! preg_match('/^batch-([1-9][0-9]*)-snapshot-([1-9][0-9]*)$/', $name, $matches)) {
                $skipped[] = ['path' => self::ROOT.'/'.$name, 'reason' => 'unexpected root entry'];
                continue;
            }
            $batchId = (int) $matches[1];
            $snapshotId = (int) $matches[2];
            $relative = self::ROOT.'/'.$name;
            $absolute = $this->safeAbsolutePath($disk, $relative, true);

            $result = $this->withPackageLock($batchId, $snapshotId, function () use (
                $disk, $absolute, $relative, $batchId, $cutoff, $apply
            ): array {
                if (ImportBatch::query()->whereKey($batchId)->exists()) {
                    return ['state' => 'skip', 'reason' => 'database batch exists; preserve package'];
                }
                $entries = scandir($absolute) ?: [];
                $allowed = ['.', '..', 'candidates.csv', 'manifest.json'];
                $newest = (int) (filemtime($absolute) ?: time());
                foreach ($entries as $entry) {
                    if ($entry === '.' || $entry === '..') {
                        continue;
                    }
                    $path = $absolute.DIRECTORY_SEPARATOR.$entry;
                    $this->assertNoSymlink($path);
                    if (! in_array($entry, $allowed, true)
                        && preg_match('/^\\.(?:candidates\\.csv|manifest\\.json)\\.tmp-[a-f0-9]{16}$/', $entry) !== 1) {
                        return ['state' => 'skip', 'reason' => 'unexpected file; manual inspection required'];
                    }
                    $newest = max($newest, (int) (filemtime($path) ?: time()));
                }
                if ($newest > $cutoff) {
                    return ['state' => 'skip', 'reason' => 'artifact is newer than the grace period'];
                }
                $paths = array_map(fn ($entry) => $relative.'/'.$entry, array_values(array_filter(
                    $entries,
                    fn ($entry) => $entry !== '.' && $entry !== '..'
                )));
                if (! $apply) {
                    return ['state' => 'candidate', 'paths' => $paths, 'reason' => 'orphan package; database batch is absent'];
                }
                foreach ($entries as $entry) {
                    if ($entry === '.' || $entry === '..') {
                        continue;
                    }
                    $path = $absolute.DIRECTORY_SEPARATOR.$entry;
                    if (is_file($path) && ! is_link($path)) {
                        if (! unlink($path)) {
                            throw new RuntimeException('Unable to remove orphan artifact: '.$relative.'/'.$entry);
                        }
                    } else {
                        throw new RuntimeException('Unexpected filesystem object; refusing deletion: '.$relative.'/'.$entry);
                    }
                }
                if (! rmdir($absolute)) {
                    throw new RuntimeException('Unable to remove empty orphan package directory: '.$relative);
                }
                return ['state' => 'deleted', 'paths' => $paths, 'reason' => 'orphan package removed after grace period'];
            });

            $record = ['path' => $relative, 'reason' => $result['reason']] + (isset($result['paths']) ? ['candidate_paths' => $result['paths']] : []);
            if ($result['state'] === 'candidate') {
                $candidates[] = $record;
            } elseif ($result['state'] === 'deleted') {
                $deleted[] = $record;
            } else {
                $skipped[] = $record;
            }
        }

        return ['candidates' => $candidates, 'skipped' => $skipped, 'deleted' => $deleted];
    }

    private function readValidPackage($disk, string $directory, ImportBatch $batch, SourceSnapshot $snapshot, int $snapshotId): ?array
    {
        $csvPath = $directory.'/candidates.csv';
        $manifestPath = $directory.'/manifest.json';
        if (! $disk->exists($csvPath) || ! $disk->exists($manifestPath)) {
            return null;
        }
        $manifest = json_decode($disk->get($manifestPath), true);
        if (! is_array($manifest)
            || ($manifest['package_type'] ?? null) !== self::PACKAGE_TYPE
            || (int) ($manifest['schema_version'] ?? 0) !== self::SCHEMA_VERSION
            || (int) ($manifest['batch_id'] ?? 0) !== (int) $batch->id
            || (int) ($manifest['snapshot_id'] ?? 0) !== $snapshotId
            || (string) ($manifest['batch_key'] ?? '') !== (string) $batch->batch_key
            || (int) ($manifest['source_id'] ?? 0) !== (int) $batch->source_id
            || (string) ($manifest['source_type'] ?? '') !== (string) $batch->source_type
            || (string) ($manifest['snapshot_hash'] ?? '') !== (string) $snapshot->content_hash
            || ($manifest['files']['csv'] ?? null) !== 'candidates.csv'
            || ! in_array($manifest['status'] ?? null, ['review_required', 'no_candidates'], true)
            || (int) ($manifest['candidate_count'] ?? -1) !== $batch->rows->count()
            || ! is_string($manifest['checksums']['candidates_csv_sha256'] ?? null)) {
            return null;
        }
        $csv = $disk->get($csvPath);
        if (! is_string($csv) || ! hash_equals($manifest['checksums']['candidates_csv_sha256'], hash('sha256', $csv))) {
            return null;
        }
        $expectedStatus = $batch->rows->isEmpty() ? 'no_candidates' : 'review_required';
        if ($manifest['status'] !== $expectedStatus) {
            return null;
        }
        return $manifest;
    }

    private function withPackageLock(int $batchId, int $snapshotId, callable $callback): mixed
    {
        $disk = Storage::disk('local');
        $this->ensureDirectory($disk, self::ROOT.'/.locks');
        $lockPath = $this->safeAbsolutePath($disk, self::ROOT.'/.locks/'.hash('sha256', $batchId.'|'.$snapshotId).'.lock', false);
        $this->assertNoSymlink($lockPath);
        $handle = fopen($lockPath, 'c+');
        if ($handle === false || ! flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new RuntimeException('Unable to acquire the review-package filesystem lock.');
        }
        try {
            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function ensureDirectory($disk, string $relative): void
    {
        $root = $this->rootPath($disk);
        if (! is_dir($root) && ! mkdir($root, 0770, true) && ! is_dir($root)) {
            throw new RuntimeException('Unable to create the private review-package root.');
        }
        $this->assertNoSymlink($root);
        $path = $disk->path($relative);
        $parent = dirname($path);
        if (! is_dir($parent) && ! mkdir($parent, 0770, true) && ! is_dir($parent)) {
            throw new RuntimeException('Unable to create a private review-package directory.');
        }
        $this->assertNoSymlink($parent);
        if (! is_dir($path) && ! mkdir($path, 0770, true) && ! is_dir($path)) {
            throw new RuntimeException('Unable to create a private review-package directory.');
        }
        $this->assertNoSymlink($path);
        $this->safeAbsolutePath($disk, $relative, true);
    }

    private function atomicWrite($disk, string $directory, string $filename, string $contents): void
    {
        $target = $this->safeAbsolutePath($disk, $directory.'/'.$filename, false);
        $temporaryName = '.'.$filename.'.tmp-'.bin2hex(random_bytes(8));
        $temporary = $this->safeAbsolutePath($disk, $directory.'/'.$temporaryName, false);
        $handle = fopen($temporary, 'x');
        if ($handle === false) {
            throw new RuntimeException('Unable to create a unique temporary review-package artifact.');
        }
        try {
            $written = 0;
            $length = strlen($contents);
            while ($written < $length) {
                $count = fwrite($handle, substr($contents, $written));
                if ($count === false || $count === 0) {
                    throw new RuntimeException('Unable to write a complete review-package artifact.');
                }
                $written += $count;
            }
            if (! fflush($handle)) {
                throw new RuntimeException('Unable to flush a review-package artifact.');
            }
        } finally {
            fclose($handle);
        }
        if (! rename($temporary, $target)) {
            throw new RuntimeException('Unable to atomically install a review-package artifact.');
        }
    }

    private function assertKnownDirectoryContents($disk, string $directory): void
    {
        $absolute = $this->safeAbsolutePath($disk, $directory, true);
        foreach (scandir($absolute) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..' || in_array($entry, ['candidates.csv', 'manifest.json'], true)
                || preg_match('/^\\.(?:candidates\\.csv|manifest\\.json)\\.tmp-[a-f0-9]{16}$/', $entry) === 1) {
                if ($entry !== '.' && $entry !== '..') {
                    $this->assertNoSymlink($absolute.DIRECTORY_SEPARATOR.$entry);
                }
                continue;
            }
            throw new RuntimeException('Unexpected file in review-package directory; refusing to overwrite it.');
        }
    }

    private function safeAbsolutePath($disk, string $relative, bool $mustExist): string
    {
        if (str_contains($relative, "\0") || str_starts_with($relative, '/') || preg_match('#(^|/)\\.\\.?(/|$)#', $relative) === 1) {
            throw new RuntimeException('Unsafe review-package path rejected.');
        }
        $root = $this->rootPath($disk);
        $path = $disk->path($relative);
        $this->assertNoSymlink($root);
        $parent = $mustExist ? $path : dirname($path);
        $realParent = realpath($parent);
        $realRoot = realpath($root);
        if ($realRoot === false || $realParent === false
            || ($realParent !== $realRoot && ! str_starts_with($realParent, $realRoot.DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('Review-package path escapes the application-owned directory.');
        }
        if (file_exists($path) || is_link($path)) {
            $this->assertNoSymlink($path);
            $real = realpath($path);
            if ($real === false || ($real !== $realRoot && ! str_starts_with($real, $realRoot.DIRECTORY_SEPARATOR))) {
                throw new RuntimeException('Review-package path resolves outside its owned directory.');
            }
        }
        return $path;
    }

    private function rootPath($disk): string
    {
        return $disk->path(self::ROOT);
    }

    private function assertNoSymlink(string $path): void
    {
        if (is_link($path)) {
            throw new RuntimeException('Symlink detected in review-package storage; refusing access.');
        }
    }

    private function spreadsheetSafeCell(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        return preg_match('/^[\\s\\x00-\\x1F]*[=+@-]/u', $value) === 1 ? "'".$value : $value;
    }
}

<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\ImportBatch;
use App\Models\SourceSnapshot;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class InstituteDiscoveryReviewPackageService
{
    private const ROOT = 'institute-discovery-review-packages';
    private const PACKAGE_TYPE = 'institute_discovery_review';
    private const SCHEMA_VERSION = 3;

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
            $batch->refresh();
            $batch->load([
                'source',
                'rows' => fn ($query) => $query->orderBy('row_number')->orderBy('id'),
            ]);
            $snapshot->refresh();

            if ($batch->status !== 'validated' || $batch->completed_at === null
                || ! $batch->source
                || (int) $batch->source_id !== (int) $snapshot->source_id
                || (int) $snapshot->id !== $snapshotId) {
                throw new RuntimeException('The review package database ownership or completion state changed before generation.');
            }

            $rowSetDigest = $this->rowSetDigest($batch);
            $evidenceState = $this->evidenceState($batch, $snapshot, $evidenceId);

            $this->ensureDirectory($disk, $directory);
            $existing = $this->readValidPackage(
                $disk,
                $directory,
                $batch,
                $snapshot,
                $snapshotId,
                $rowSetDigest,
                $evidenceState['digest']
            );
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
                'row_set_sha256' => $rowSetDigest,
                'evidence_set_sha256' => $evidenceState['digest'],
                'evidence_ids' => $evidenceState['ids'],
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

            $verified = $this->readValidPackage(
                $disk,
                $directory,
                $batch,
                $snapshot,
                $snapshotId,
                $rowSetDigest,
                $evidenceState['digest']
            );
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
            if (! is_dir($absolute)) {
                $skipped[] = ['path' => $relative, 'reason' => 'expected package directory is not a directory'];
                continue;
            }

            $result = $this->withPackageLock($batchId, $snapshotId, function () use (
                $disk, $absolute, $relative, $batchId, $cutoff, $apply
            ): array {
                $existingBatch = ImportBatch::query()->find($batchId);
                if ($existingBatch) {
                    $staleTemps = [];
                    foreach (scandir($absolute) ?: [] as $entry) {
                        if (preg_match('/^\\.(?:candidates\\.csv|manifest\\.json)\\.tmp-[a-f0-9]{16}$/', $entry) !== 1) {
                            continue;
                        }
                        $tempPath = $absolute.DIRECTORY_SEPARATOR.$entry;
                        $this->assertNoSymlink($tempPath);
                        if (! is_file($tempPath)) {
                            return ['state' => 'skip', 'reason' => 'temporary artifact is not a regular file'];
                        }
                        if ((int) (filemtime($tempPath) ?: time()) <= $cutoff) {
                            $staleTemps[] = $tempPath;
                        }
                    }
                    if ($staleTemps === []) {
                        return ['state' => 'skip', 'reason' => 'database batch exists; preserve package'];
                    }
                    $paths = array_map(fn ($path) => $relative.'/'.basename($path), $staleTemps);
                    if (! $apply) {
                        return ['state' => 'candidate', 'paths' => $paths, 'reason' => 'stale temporary artifacts; preserve package'];
                    }
                    foreach ($staleTemps as $tempPath) {
                        if (! unlink($tempPath)) {
                            throw new RuntimeException('Unable to remove stale temporary artifact: '.$tempPath);
                        }
                    }
                    return ['state' => 'deleted', 'paths' => $paths, 'reason' => 'stale temporary artifacts removed; package preserved'];
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

    private function readValidPackage($disk, string $directory, ImportBatch $batch, SourceSnapshot $snapshot, int $snapshotId, string $rowSetDigest, string $evidenceSetDigest): ?array
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
            || (string) ($manifest['entity_type'] ?? '') !== (string) $batch->entity_type
            || (string) ($manifest['snapshot_hash'] ?? '') !== (string) $snapshot->content_hash
            || ! hash_equals($rowSetDigest, (string) ($manifest['row_set_sha256'] ?? ''))
            || ! hash_equals($evidenceSetDigest, (string) ($manifest['evidence_set_sha256'] ?? ''))
            || ($manifest['canonical_mutations_performed'] ?? null) !== false
            || ($manifest['automatic_approval_performed'] ?? null) !== false
            || ($manifest['files']['csv'] ?? null) !== 'candidates.csv'
            || ! in_array($manifest['status'] ?? null, ['review_required', 'no_candidates'], true)
            || (int) ($manifest['candidate_count'] ?? -1) !== $batch->rows->count()
            || ! is_string($manifest['checksums']['candidates_csv_sha256'] ?? null)
            || preg_match('/^[a-f0-9]{64}$/', $manifest['checksums']['candidates_csv_sha256']) !== 1) {
            return null;
        }
        $csv = $disk->get($csvPath);
        if (! is_string($csv) || ! hash_equals($manifest['checksums']['candidates_csv_sha256'], hash('sha256', $csv))) {
            return null;
        }
        if ($this->csvDataRowCount($csv) !== $batch->rows->count()) {
            return null;
        }
        $expectedStatus = $batch->rows->isEmpty() ? 'no_candidates' : 'review_required';
        if ($manifest['status'] !== $expectedStatus) {
            return null;
        }
        return $manifest;
    }

    /**
     * Hash the complete ordered database row set represented by the CSV. The
     * digest intentionally includes fields not currently displayed in the CSV,
     * so any persisted review-relevant row mutation invalidates reuse.
     */
    private function rowSetDigest(ImportBatch $batch): string
    {
        $rows = $batch->rows
            ->sortBy([['row_number', 'asc'], ['id', 'asc']])
            ->values()
            ->map(fn ($row): array => $this->canonicalize([
                'id' => (int) $row->id,
                'row_number' => (int) $row->row_number,
                'status' => $row->status,
                'match_state' => $row->match_state,
                'canonical_subject_type' => $row->canonical_subject_type,
                'canonical_subject_id' => $row->canonical_subject_id,
                'parent_subject_type' => $row->parent_subject_type,
                'parent_subject_id' => $row->parent_subject_id,
                'payload' => $row->payload,
                'normalized_payload' => $row->normalized_payload,
                'validation_errors' => $row->validation_errors,
                'notes' => $row->notes,
                'discovery_identity_key' => $row->discovery_identity_key,
            ]))
            ->all();

        return hash('sha256', json_encode($rows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /**
     * Validate that every referenced evidence record belongs to this batch's
     * source and exact snapshot before it is represented in a review package.
     *
     * @return array{ids:array<int>,digest:string}
     */
    private function evidenceState(ImportBatch $batch, SourceSnapshot $snapshot, ?int $evidenceId): array
    {
        $ids = collect([$evidenceId]);

        foreach ($batch->rows as $row) {
            $payload = is_array($row->payload) ? $row->payload : [];
            $provenance = is_array($payload['_provenance'] ?? null) ? $payload['_provenance'] : [];
            if (isset($provenance['evidence_id']) && (int) $provenance['evidence_id'] > 0) {
                $ids->push((int) $provenance['evidence_id']);
            }
        }

        if ($batch->discovery_identity_key !== null) {
            $expectedEvidenceKey = app(DiscoveryPersistenceIdentityService::class)
                ->evidenceKey((string) $batch->discovery_identity_key);
            $derivedEvidenceId = Evidence::query()
                ->where('source_id', $batch->source_id)
                ->where('snapshot_id', $snapshot->id)
                ->where('discovery_identity_key', $expectedEvidenceKey)
                ->value('id');

            if ($derivedEvidenceId !== null) {
                $ids->push((int) $derivedEvidenceId);
            }
        }

        $ids = $ids->filter(fn ($id) => (int) $id > 0)->map(fn ($id) => (int) $id)->unique()->sort()->values();
        $records = Evidence::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get(['id', 'source_id', 'snapshot_id', 'discovery_identity_key', 'reference', 'content_hash', 'observed_at', 'published_at', 'expires_at', 'status']);

        if ($records->count() !== $ids->count()) {
            throw new RuntimeException('A review-package evidence reference does not resolve to a persisted evidence record.');
        }

        foreach ($records as $record) {
            if ((int) $record->source_id !== (int) $batch->source_id
                || (int) $record->snapshot_id !== (int) $snapshot->id) {
                throw new RuntimeException('Review-package evidence does not belong to the batch source and snapshot.');
            }

            if ($batch->discovery_identity_key !== null) {
                $expectedEvidenceKey = app(DiscoveryPersistenceIdentityService::class)
                    ->evidenceKey((string) $batch->discovery_identity_key);

                if (! hash_equals($expectedEvidenceKey, (string) $record->discovery_identity_key)) {
                    throw new RuntimeException('Review-package evidence identity does not match the discovery batch.');
                }
            }
        }

        $evidenceRows = $records->map(fn ($record): array => [
            'id' => (int) $record->id,
            'source_id' => (int) $record->source_id,
            'snapshot_id' => (int) $record->snapshot_id,
            'discovery_identity_key' => $record->discovery_identity_key,
            'reference' => $record->reference,
            'content_hash' => $record->content_hash,
            'observed_at' => $record->observed_at?->toIso8601String(),
            'published_at' => $record->published_at?->toIso8601String(),
            'expires_at' => $record->expires_at?->toIso8601String(),
            'status' => $record->status,
        ])->all();

        return [
            'ids' => $ids->all(),
            'digest' => hash('sha256', json_encode($evidenceRows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
        ];
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map($this->canonicalize(...), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }

    private function withPackageLock(int $batchId, int $snapshotId, callable $callback): mixed
    {
        $disk = Storage::disk('local');
        $this->ensureDirectory($disk, self::ROOT.'/.locks');
        $lockPath = $this->safeAbsolutePath($disk, self::ROOT.'/.locks/'.hash('sha256', $batchId.'|'.$snapshotId).'.lock', false);
        $this->assertNoSymlink($lockPath);
        $handle = fopen($lockPath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Unable to open the review-package filesystem lock.');
        }
        $opened = fstat($handle);
        $current = lstat($lockPath);
        if (is_link($lockPath) || $opened === false || $current === false
            || $opened['dev'] !== $current['dev'] || $opened['ino'] !== $current['ino']
            || ! flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new RuntimeException('Unable to safely acquire the review-package filesystem lock.');
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


    private function csvDataRowCount(string $csv): ?int
    {
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            return null;
        }
        try {
            if (fwrite($stream, $csv) !== strlen($csv)) {
                return null;
            }
            rewind($stream);
            $headers = fgetcsv($stream);
            $expected = [
                'batch_id', 'row_id', 'row_number', 'entity_type', 'institution_id',
                'candidate_name', 'candidate_slug', 'official_source_url', 'snapshot_id',
                'evidence_id', 'observed_at', 'confidence', 'extraction_method',
                'duplicate_state', 'validation_errors', 'review_status',
            ];
            if ($headers !== $expected) {
                return null;
            }
            $count = 0;
            while (($row = fgetcsv($stream)) !== false) {
                if (count($row) !== count($expected)) {
                    return null;
                }
                $count++;
            }
            return $count;
        } finally {
            fclose($stream);
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

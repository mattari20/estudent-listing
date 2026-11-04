<?php

declare(strict_types=1);

use App\Models\ImportBatch;
use App\Models\Source;
use App\Models\SourceSnapshot;
use App\Services\InstituteDiscoveryReviewPackageService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Storage;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';

if (($argv[1] ?? null) === 'worker') {
    $mode = $argv[2] ?? 'writer';
    $batchId = (int) ($argv[3] ?? 0);
    $ready = $argv[4] ?? '';
    $go = $argv[5] ?? '';
    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    file_put_contents($ready, 'ready');
    $deadline = microtime(true) + 15;
    while (! is_file($go) && microtime(true) < $deadline) {
        usleep(25000);
    }
    if (! is_file($go)) {
        fwrite(STDERR, "Worker did not receive the package-generation barrier.\n");
        exit(2);
    }
    $service = app(InstituteDiscoveryReviewPackageService::class);
    if ($mode === 'cleanup') {
        $result = $service->reconcileOrphans(24, true);
    } else {
        $batch = ImportBatch::query()->findOrFail($batchId);
        $result = $service->create($batch);
    }
    fwrite(STDOUT, json_encode($result, JSON_THROW_ON_ERROR)."\n");
    exit(0);
}

if (getenv('DB_CONNECTION') !== 'mysql') {
    fwrite(STDERR, "Review-package concurrency proof requires isolated MySQL.\n");
    exit(2);
}

$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$source = Source::create([
    'source_type' => 'official',
    'name' => 'CI review package concurrency source',
    'url' => 'https://example.edu/review-package-concurrency',
    'authority_scope' => 'CI-only fixture',
    'status' => 'active',
]);
$snapshot = SourceSnapshot::create([
    'source_id' => $source->id,
    'reference' => $source->url,
    'content_hash' => hash('sha256', 'review package concurrency fixture'),
    'content_length' => 34,
    'content_type' => 'text/html',
    'content' => 'review package concurrency fixture',
    'captured_at' => now(),
    'status' => 'captured',
    'metadata' => [],
]);
$batch = ImportBatch::create([
    'batch_key' => 'ci-review-package-concurrency-'.bin2hex(random_bytes(6)),
    'entity_type' => 'institution',
    'source_type' => 'automated_discovery',
    'source_id' => $source->id,
    'status' => 'validated',
    'total_rows' => 0,
    'valid_rows' => 0,
    'invalid_rows' => 0,
    'candidate_rows' => 0,
    'received_at' => now(),
    'completed_at' => now(),
    'notes' => 'No candidates extracted from source snapshot #'.$snapshot->id.'.',
]);

$barrier = sys_get_temp_dir().'/review-package-'.bin2hex(random_bytes(8));
if (! mkdir($barrier, 0700, true) && ! is_dir($barrier)) {
    throw new RuntimeException('Unable to create review-package concurrency barrier.');
}
$go = $barrier.'/go';
$workers = [];
$directory = 'institute-discovery-review-packages/batch-'.$batch->id.'-snapshot-'.$snapshot->id;
$lock = Storage::disk('local')->path('institute-discovery-review-packages/.locks/'.hash('sha256', $batch->id.'|'.$snapshot->id).'.lock');
$staleTemp = $directory.'/.candidates.csv.tmp-0123456789abcdef';
Storage::disk('local')->put($staleTemp, 'interrupted stale temporary artifact');
touch(Storage::disk('local')->path($staleTemp), now()->subDays(2)->timestamp);

try {
    foreach (['writer', 'writer-second', 'cleanup'] as $label) {
        $ready = $barrier.'/'.$label.'.ready';
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, __FILE__, 'worker', $label, (string) $batch->id, $ready, $go],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root
        );
        if (! is_resource($process)) {
            throw new RuntimeException('Unable to start review-package worker.');
        }
        fclose($pipes[0]);
        $workers[] = [$process, $pipes, $ready];
    }

    $deadline = microtime(true) + 15;
    while ((! is_file($workers[0][2]) || ! is_file($workers[1][2])) && microtime(true) < $deadline) {
        usleep(25000);
    }
    if (! is_file($workers[0][2]) || ! is_file($workers[1][2])) {
        throw new RuntimeException('Both review-package workers did not reach the start barrier.');
    }
    file_put_contents($go, 'go');

    $results = [];
    foreach ($workers as [$process, $pipes]) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit !== 0) {
            throw new RuntimeException('Review-package worker failed: '.trim((string) $stderr).' '.trim((string) $stdout));
        }
        $results[] = json_decode(trim((string) $stdout), true, 512, JSON_THROW_ON_ERROR);
    }

    $writerResults = [$results[0], $results[1]];
    if (count(array_filter($writerResults, fn ($result) => ($result['status'] ?? null) === 'created')) !== 1
        || count(array_filter($writerResults, fn ($result) => ($result['status'] ?? null) === 'reused')) !== 1
        || ($writerResults[0]['csv_path'] ?? null) !== ($writerResults[1]['csv_path'] ?? null)
        || ($writerResults[0]['manifest_path'] ?? null) !== ($writerResults[1]['manifest_path'] ?? null)) {
        throw new RuntimeException('Concurrent package writers did not converge on one deterministic package pair.');
    }

    $disk = Storage::disk('local');
    $manifestPath = $directory.'/manifest.json';
    $csvPath = $directory.'/candidates.csv';
    $manifest = json_decode($disk->get($manifestPath), true);
    $csv = $disk->get($csvPath);
    if (! is_array($manifest)
        || (int) ($manifest['batch_id'] ?? 0) !== (int) $batch->id
        || (int) ($manifest['snapshot_id'] ?? 0) !== (int) $snapshot->id
        || ! hash_equals((string) ($manifest['checksums']['candidates_csv_sha256'] ?? ''), hash('sha256', $csv))) {
        throw new RuntimeException('Concurrent review-package pair failed final identity/checksum validation.');
    }

    if ($disk->exists($staleTemp)) {
        throw new RuntimeException('The cleanup process did not reconcile the stale temporary artifact.');
    }
    fwrite(STDOUT, "PASS: two independent package writers and a cleanup process shared the per-package lock; one writer created the pair, the other reused it, and the stale temporary artifact was reconciled. No database candidate rows were changed.\n");
} finally {
    foreach ($workers as [$process, $pipes]) {
        if (is_resource($process)) {
            foreach ([1, 2] as $index) {
                if (isset($pipes[$index]) && is_resource($pipes[$index])) {
                    fclose($pipes[$index]);
                }
            }
            @proc_terminate($process);
            @proc_close($process);
        }
    }
    Storage::disk('local')->deleteDirectory($directory);
    if (is_file($lock) && ! is_link($lock)) {
        @unlink($lock);
    }
    foreach (glob($barrier.'/*') ?: [] as $path) {
        @unlink($path);
    }
    @rmdir($barrier);
}

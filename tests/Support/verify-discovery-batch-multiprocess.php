<?php

declare(strict_types=1);

use App\Models\Evidence;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Source;
use App\Models\SourceSnapshot;
use App\Services\InstituteDiscoveryAgentService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';

if (($argv[1] ?? null) === 'worker') {
    $profile = $argv[2] ?? '';
    $snapshotId = (int) ($argv[3] ?? 0);
    $readyPath = $argv[4] ?? '';
    $goPath = $argv[5] ?? '';

    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();

    file_put_contents($readyPath, 'ready');
    $deadline = microtime(true) + 15;
    while (! is_file($goPath) && microtime(true) < $deadline) {
        usleep(25000);
    }

    if (! is_file($goPath)) {
        fwrite(STDERR, "Worker did not receive the concurrent-start barrier.\n");
        exit(2);
    }

    $snapshot = SourceSnapshot::query()->findOrFail($snapshotId);
    $summary = app(InstituteDiscoveryAgentService::class)->runFromSnapshot($profile, $snapshot, false);
    fwrite(STDOUT, json_encode([
        'batch_id' => $summary['batch_id'],
        'candidate_rows' => $summary['candidate_rows'],
        'review_package_status' => $summary['review_package']['status'] ?? null,
    ], JSON_THROW_ON_ERROR)."\n");
    exit(0);
}

if (getenv('DB_CONNECTION') !== 'mysql') {
    fwrite(STDERR, "This candidate-batch concurrency proof must run against isolated MySQL.\n");
    exit(2);
}

$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$profile = 'cui-lahore';
$definition = config("institute_agent.profiles.{$profile}");
if (! is_array($definition)) {
    throw new RuntimeException("Required CI discovery profile {$profile} is not configured.");
}

$sourceDefinition = $definition['source'] ?? [];
$source = Source::create([
    'source_type' => $sourceDefinition['type'] ?? 'official',
    'name' => $sourceDefinition['name'] ?? 'CI candidate concurrency source',
    'url' => $sourceDefinition['url'] ?? '',
    'authority_scope' => $sourceDefinition['authority_scope'] ?? null,
    'status' => 'active',
]);

$parentSlug = (string) ($definition['institution_slug'] ?? '');
if ($parentSlug === '') {
    throw new RuntimeException('The CI discovery profile has no canonical parent institution slug.');
}

Institution::create([
    'name' => 'CI Candidate Concurrency Institution',
    'slug' => $parentSlug,
    'status' => 'active',
]);

$html = '<html><body>
    <a>Bachelor of Science (BS) in Computer Science</a>
    <a>Bachelor of Science (BS) in Psychology</a>
</body></html>';

$snapshot = SourceSnapshot::create([
    'source_id' => $source->id,
    'reference' => $source->url,
    'content_hash' => hash('sha256', $html),
    'content_length' => strlen($html),
    'content' => $html,
    'captured_at' => now(),
    'status' => 'captured',
]);

$directory = sys_get_temp_dir().'/discovery-batch-'.bin2hex(random_bytes(8));
if (! mkdir($directory, 0700, true) && ! is_dir($directory)) {
    throw new RuntimeException('Unable to create the candidate concurrency barrier directory.');
}

$goPath = $directory.'/go';
$workers = [];

$startWorker = static function (string $label) use ($directory, $goPath, $profile, $snapshot, $root): array {
    $readyPath = $directory.'/'.$label.'.ready';
    $pipes = [];
    $process = proc_open(
        [PHP_BINARY, __FILE__, 'worker', $profile, (string) $snapshot->id, $readyPath, $goPath],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $root
    );

    if (! is_resource($process)) {
        throw new RuntimeException("Unable to start candidate persistence worker {$label}.");
    }

    fclose($pipes[0]);

    return [$process, $pipes, $readyPath];
};

try {
    $workers[] = $startWorker('first');
    $workers[] = $startWorker('second');

    $deadline = microtime(true) + 15;
    while ((! is_file($workers[0][2]) || ! is_file($workers[1][2])) && microtime(true) < $deadline) {
        usleep(25000);
    }

    if (! is_file($workers[0][2]) || ! is_file($workers[1][2])) {
        throw new RuntimeException('Both candidate persistence workers did not reach the concurrent-start barrier.');
    }

    file_put_contents($goPath, 'go');

    $results = [];
    foreach ($workers as [$process, $pipes]) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new RuntimeException('Candidate persistence worker failed: '.trim((string) $stderr).' '.trim((string) $stdout));
        }

        $result = json_decode(trim((string) $stdout), true, 512, JSON_THROW_ON_ERROR);
        $results[] = $result;
    }

    if (($results[0]['batch_id'] ?? null) !== ($results[1]['batch_id'] ?? null)) {
        throw new RuntimeException('Concurrent workers returned different candidate batch IDs.');
    }

    $batchId = (int) $results[0]['batch_id'];
    $batch = ImportBatch::query()->findOrFail($batchId);
    $batchCount = ImportBatch::query()->where('discovery_identity_key', $batch->discovery_identity_key)->count();
    $rowCount = ImportRow::query()->where('import_batch_id', $batchId)->count();
    $evidenceCount = Evidence::query()->where('discovery_identity_key', '!=', null)->count();
    $assertionCount = DB::table('assertions')->whereNotNull('discovery_identity_key')->count();

    if ($batchCount !== 1 || $rowCount !== 2 || $evidenceCount !== 1 || $assertionCount !== 2) {
        throw new RuntimeException("Unexpected candidate persistence counts: batches={$batchCount}, rows={$rowCount}, evidence={$evidenceCount}, assertions={$assertionCount}.");
    }

    if ($batch->status !== 'validated' || $batch->completed_at === null) {
        throw new RuntimeException('The shared candidate batch was not durably finalized.');
    }

    fwrite(STDOUT, "PASS: two independent PHP processes concurrently persisted one logical discovery batch; both returned batch #{$batchId}, with one batch, two candidate rows, one evidence record and two assertion links.\n");
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

    foreach (glob($directory.'/*') ?: [] as $file) {
        @unlink($file);
    }
    @rmdir($directory);
}

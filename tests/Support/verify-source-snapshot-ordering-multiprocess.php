<?php

declare(strict_types=1);

use App\Models\Source;
use App\Models\SourceChangeObservation;
use App\Models\SourceRetrievalRun;
use App\Models\SourceSnapshot;
use App\Models\SourceSnapshotReconciliationRequest;
use App\Services\SourceChangeObservationService;
use App\Services\SourceRetrievalService;
use App\Services\SourceSnapshotService;
use App\Services\SourceUrlGuard;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

$root = dirname(__DIR__, 2);

if (($argv[1] ?? '') === 'worker') {
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();

    [, , $sourceId, $content, $capturedAt, $key, $ready, $go] = $argv;
    file_put_contents($ready, 'ready');
    $deadline = microtime(true) + 30;
    while (! is_file($go) && microtime(true) < $deadline) {
        usleep(10000);
    }
    if (! is_file($go)) {
        fwrite(STDERR, "worker barrier timed out\n");
        exit(2);
    }

    try {
        Carbon::setTestNow(Carbon::parse($capturedAt));
        app()->instance(SourceUrlGuard::class, new SourceUrlGuard(
            static fn (string $host, int $type): array => [['host' => $host, 'type' => 'A', 'ip' => '93.184.216.34']]
        ));
        Http::fake([
            'https://example.test/*' => Http::response($content, 200, ['Content-Type' => 'text/plain']),
        ]);
        $source = Source::query()->findOrFail((int) $sourceId);
        $run = app(SourceRetrievalService::class)->retrieve($source, $key);
        if ($run->status !== 'completed' || ! $run->snapshot_id) {
            throw new RuntimeException('Mocked concurrent source retrieval did not complete.');
        }
        $snapshot = SourceSnapshot::query()->findOrFail($run->snapshot_id);
        $observation = app(SourceChangeObservationService::class)->compare($snapshot);
        fwrite(STDOUT, json_encode([
            'run_id' => $run->id,
            'snapshot_id' => $snapshot->id,
            'previous_snapshot_id' => $observation->previous_snapshot_id,
            'change_state' => $observation->change_state,
        ], JSON_THROW_ON_ERROR)."\n");
        Carbon::setTestNow();
        exit(0);
    } catch (Throwable $exception) {
        Carbon::setTestNow();
        fwrite(STDERR, $exception::class.': '.$exception->getMessage()."\n");
        exit(1);
    }
}

if (getenv('DB_CONNECTION') !== 'mysql') {
    fwrite(STDERR, "Snapshot chronology concurrency proof requires isolated MySQL.\n");
    exit(2);
}

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$source = Source::query()->create([
    'source_type' => 'official',
    'name' => 'Phase 1C-22 chronology multiprocess '.bin2hex(random_bytes(4)),
    'url' => 'https://example.test/phase-1c-22',
    'status' => 'active',
]);
$fixtures = [$source->id];

$runPair = function (array $workers, string $label) use ($root): array {
    $barrier = sys_get_temp_dir().'/snapshot-chronology-'.bin2hex(random_bytes(8));
    if (! mkdir($barrier, 0700, true) && ! is_dir($barrier)) {
        throw new RuntimeException('Unable to create snapshot chronology process barrier.');
    }
    $go = $barrier.'/go';
    $processes = [];
    $results = [];

    try {
        foreach ($workers as $index => $worker) {
            $ready = $barrier.'/worker-'.$index.'.ready';
            $pipes = [];
            $command = [
                PHP_BINARY,
                __FILE__,
                'worker',
                (string) $worker['source_id'],
                $worker['content'],
                $worker['captured_at'],
                $worker['key'],
                $ready,
                $go,
            ];
            $process = proc_open($command, [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ], $pipes, $root);

            if (! is_resource($process)) {
                throw new RuntimeException('Unable to start independent snapshot retrieval worker.');
            }
            fclose($pipes[0]);
            $processes[] = [$process, $pipes, $ready];
        }

        $deadline = microtime(true) + 30;
        while (count(array_filter($processes, static fn (array $entry): bool => is_file($entry[2]))) !== count($processes)
            && microtime(true) < $deadline) {
            usleep(10000);
        }
        if (count(array_filter($processes, static fn (array $entry): bool => is_file($entry[2]))) !== count($processes)) {
            throw new RuntimeException($label.' workers did not reach the start barrier.');
        }

        file_put_contents($go, 'go');
        foreach ($processes as [$process, $pipes]) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exit = proc_close($process);
            if ($exit !== 0) {
                throw new RuntimeException($label.' worker failed: '.trim((string) $stderr).' '.trim((string) $stdout));
            }
            $results[] = json_decode(trim((string) $stdout), true, 512, JSON_THROW_ON_ERROR);
        }
    } finally {
        foreach ($processes as [$process, $pipes]) {
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
        foreach (glob($barrier.'/*') ?: [] as $path) {
            @unlink($path);
        }
        @rmdir($barrier);
    }

    return $results;
};

try {
    $snapshots = app(SourceSnapshotService::class);
    $comparisons = app(SourceChangeObservationService::class);

    $a = $snapshots->capture(
        $source,
        'A',
        'https://example.test/multiprocess',
        'text/plain',
        [],
        'snapshot-A',
        Carbon::parse('2026-11-01 10:00:00'),
    );
    $aObservation = $comparisons->compare($a);
    if ($aObservation->previous_snapshot_id !== null) {
        throw new RuntimeException('A did not become the baseline snapshot.');
    }

    // Two retrieval workers race for the same source. B is chronologically
    // earlier than C even if C's worker completes and compares first.
    $results = $runPair([
        [
            'source_id' => $source->id,
            'content' => 'B',
            'captured_at' => '2026-11-02 10:00:00',
            'key' => 'retrieval-B-'.bin2hex(random_bytes(3)),
        ],
        [
            'source_id' => $source->id,
            'content' => 'C',
            'captured_at' => '2026-11-03 10:00:00',
            'key' => 'retrieval-C-'.bin2hex(random_bytes(3)),
        ],
    ], 'concurrent retrieval');

    $b = SourceSnapshot::query()->where('source_id', $source->id)->where('content_hash', hash('sha256', 'B'))->firstOrFail();
    $c = SourceSnapshot::query()->where('source_id', $source->id)->where('content_hash', hash('sha256', 'C'))->firstOrFail();
    $bObservation = SourceChangeObservation::query()->where('current_snapshot_id', $b->id)->firstOrFail();
    $cObservation = SourceChangeObservation::query()->where('current_snapshot_id', $c->id)->firstOrFail();

    if ((int) $bObservation->previous_snapshot_id !== (int) $a->id) {
        throw new RuntimeException('Concurrent B comparison did not select A.');
    }
    if ((int) ($cObservation->previous_snapshot_id ?? 0) !== (int) $b->id) {
        $request = SourceSnapshotReconciliationRequest::query()
            ->where('current_snapshot_id', $c->id)
            ->where('expected_previous_snapshot_id', $b->id)
            ->where('recorded_previous_snapshot_id', $a->id)
            ->where('status', 'pending_review')
            ->first();
        if (! $request) {
            throw new RuntimeException('Concurrent out-of-order C comparison did not create a pending reconciliation request.');
        }
    }

    // Replaying the same capture identity and comparison must not add history.
    $bRun = SourceRetrievalRun::query()->where('snapshot_id', $b->id)->firstOrFail();
    app()->instance(SourceUrlGuard::class, new SourceUrlGuard(
        static fn (string $host, int $type): array => [['host' => $host, 'type' => 'A', 'ip' => '93.184.216.34']]
    ));
    Http::fake(['https://example.test/*' => Http::response('B', 200, ['Content-Type' => 'text/plain'])]);
    Carbon::setTestNow(Carbon::parse('2026-11-02 10:00:00'));
    $bReplayRun = app(SourceRetrievalService::class)->retrieve($source, (string) $bRun->idempotency_key);
    Carbon::setTestNow();
    if ((int) $bReplayRun->id !== (int) $bRun->id || (int) $bReplayRun->snapshot_id !== (int) $b->id) {
        throw new RuntimeException('Completed retrieval replay did not reuse its original snapshot.');
    }
    Http::assertNothingSent();
    $comparisons->compare($b);
    $comparisons->compare($c);

    $snapshotCount = SourceSnapshot::query()->where('source_id', $source->id)->count();
    $observationCount = SourceChangeObservation::query()->where('source_id', $source->id)->count();
    $requestCount = SourceSnapshotReconciliationRequest::query()->where('source_id', $source->id)->count();
    $expectedRequests = $cObservation->previous_snapshot_id === $b->id ? 0 : 1;

    if ($snapshotCount !== 3 || $observationCount !== 3 || $requestCount !== $expectedRequests) {
        throw new RuntimeException("Unexpected concurrent state: snapshots={$snapshotCount}, observations={$observationCount}, reconciliation_requests={$requestCount}.");
    }

    // Force a late arrival after C was already compared. The historical C
    // observation must remain unchanged and receive one idempotent repair request.
    $lateSource = Source::query()->create([
        'source_type' => 'official',
        'name' => 'Phase 1C-22 late-arrival '.bin2hex(random_bytes(4)),
        'url' => 'https://example.test/phase-1c-22-late',
        'status' => 'active',
    ]);
    $fixtures[] = $lateSource->id;
    $lateA = $snapshots->capture($lateSource, 'late-A', null, 'text/plain', [], 'late-A', Carbon::parse('2026-11-01 10:00:00'));
    $lateC = $snapshots->capture($lateSource, 'late-C', null, 'text/plain', [], 'late-C', Carbon::parse('2026-11-03 10:00:00'));
    $comparisons->compare($lateA);
    $lateCObservation = $comparisons->compare($lateC);
    $lateB = $snapshots->capture($lateSource, 'late-B', null, 'text/plain', [], 'late-B', Carbon::parse('2026-11-02 10:00:00'));
    $lateBObservation = $comparisons->compare($lateB);
    $lateCObservation->refresh();
    $request = SourceSnapshotReconciliationRequest::query()
        ->where('current_snapshot_id', $lateC->id)
        ->where('expected_previous_snapshot_id', $lateB->id)
        ->where('recorded_previous_snapshot_id', $lateA->id)
        ->firstOrFail();

    if ((int) $lateBObservation->previous_snapshot_id !== (int) $lateA->id
        || (int) $lateCObservation->previous_snapshot_id !== (int) $lateA->id
        || $request->status !== 'pending_review') {
        throw new RuntimeException('Late arrival did not preserve history and enqueue the exact predecessor repair.');
    }
    $comparisons->compare($lateB);
    if (SourceSnapshotReconciliationRequest::query()->where('current_snapshot_id', $lateC->id)->count() !== 1) {
        throw new RuntimeException('Late-arrival reconciliation request was not idempotent.');
    }

    fwrite(STDOUT, "PASS: isolated MySQL independent processes created and compared same-source retrieval snapshots concurrently; B selected A; C either selected B or retained its prior A link with an explicit pending reconciliation; late B arrival preserved C history and created one idempotent reconciliation request.\n");
} finally {
    foreach ($fixtures as $sourceId) {
        $snapshotIds = SourceSnapshot::query()->where('source_id', $sourceId)->pluck('id');
        SourceSnapshotReconciliationRequest::query()->where('source_id', $sourceId)->delete();
        SourceChangeObservation::query()->where('source_id', $sourceId)->delete();
        SourceRetrievalRun::query()->where('source_id', $sourceId)->delete();
        SourceSnapshot::query()->whereIn('id', $snapshotIds)->delete();
        Source::query()->whereKey($sourceId)->delete();
    }
}

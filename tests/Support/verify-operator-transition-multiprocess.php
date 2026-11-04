<?php

declare(strict_types=1);

use App\Models\Assertion;
use App\Models\Evidence;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportBatch;
use App\Models\ImportPromotionEvent;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Source;
use App\Services\IdentityResolutionDecisionService;
use App\Services\OperatorTransitionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';

if (($argv[1] ?? null) === 'worker') {
    $action = $argv[2] ?? '';
    $rowId = (int) ($argv[3] ?? 0);
    $ready = $argv[4] ?? '';
    $go = $argv[5] ?? '';
    $actorId = (int) ($argv[6] ?? 0);
    $reason = $argv[7] ?? '';
    $institutionId = (int) ($argv[8] ?? 0);
    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    file_put_contents($ready, 'ready');
    $deadline = microtime(true) + 20;
    while (! is_file($go) && microtime(true) < $deadline) {
        usleep(20000);
    }
    if (! is_file($go)) {
        fwrite(STDERR, "Operator-transition worker did not receive its start barrier.\n");
        exit(2);
    }

    $row = ImportRow::query()->findOrFail($rowId);
    try {
        if ($action === 'reject') {
            app(OperatorTransitionService::class)->rejectRow($row, $actorId, $reason);
            $result = 'rejected';
        } elseif ($action === 'decision') {
            try {
                $result = app(IdentityResolutionDecisionService::class)->record($row, $actorId, 'approved_match', $institutionId, $reason)->id;
            } catch (RuntimeException) {
                $result = 'blocked';
            }
        } elseif ($action === 'publish') {
            $result = app(OperatorTransitionService::class)->publishInstitution($row, $actorId)->id;
        } elseif ($action === 'guard') {
            try {
                app(IdentityResolutionDecisionService::class)->record($row, $actorId, 'approved_match', $institutionId, $reason);
                $result = 'accepted';
            } catch (RuntimeException) {
                $result = 'blocked';
            }
        } elseif ($action === 'fail-audit-reject') {
            try {
                DB::transaction(function () use ($row, $actorId, $reason): void {
                    ImportRow::query()->whereKey($row->id)->lockForUpdate()->firstOrFail();
                    $hold = getenv('OPERATOR_HOLD_SIGNAL');
                    if ($hold) {
                        file_put_contents($hold, 'locked');
                    }
                    DB::connection()->beforeExecuting(function (string $query): void {
                        $sql = strtolower(ltrim($query));
                        if (str_starts_with($sql, 'insert') && str_contains($sql, 'import_promotion_events')) {
                            usleep(500000);
                            throw new RuntimeException('injected concurrent audit failure');
                        }
                    });
                    app(OperatorTransitionService::class)->rejectRow($row, $actorId, $reason);
                });
                $result = 'unexpected_success';
            } catch (RuntimeException $exception) {
                if ($exception->getMessage() !== 'injected concurrent audit failure') {
                    throw $exception;
                }
                $result = 'failed_and_rolled_back';
            }
        } else {
            fwrite(STDERR, "Unknown operator-transition worker action.\n");
            exit(2);
        }
        fwrite(STDOUT, json_encode(['result' => $result], JSON_THROW_ON_ERROR)."\n");
        exit(0);
    } catch (Throwable $exception) {
        fwrite(STDERR, $exception::class.': '.$exception->getMessage()."\n");
        exit(1);
    }
}

if (getenv('DB_CONNECTION') !== 'mysql') {
    fwrite(STDERR, "Operator transition concurrency proof requires isolated MySQL.\n");
    exit(2);
}

$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$createRow = function (string $status = 'candidate', bool $withEvidence = false): array {
    $institution = Institution::create([
        'name' => 'Phase 1C-21 multiprocess '.bin2hex(random_bytes(4)),
        'official_name' => 'Phase 1C-21 multiprocess institution',
        'slug' => 'phase-1c-21-multiprocess-'.bin2hex(random_bytes(4)),
        'status' => $status === 'promoted' ? 'candidate' : 'active',
    ]);
    $batch = ImportBatch::create([
        'batch_key' => 'phase-1c-21-multiprocess-'.bin2hex(random_bytes(5)),
        'entity_type' => 'institution',
        'source_type' => 'manual',
        'status' => 'validated',
        'total_rows' => 1,
        'valid_rows' => 1,
        'candidate_rows' => 1,
        'received_at' => now(),
        'completed_at' => now(),
    ]);
    $row = ImportRow::create([
        'import_batch_id' => $batch->id,
        'row_number' => 1,
        'status' => $status,
        'match_state' => 'new_candidate',
        'payload' => ['name' => $institution->name, 'slug' => $institution->slug],
        'normalized_payload' => ['name' => $institution->name, 'official_name' => $institution->official_name, 'slug' => $institution->slug],
        'canonical_subject_type' => $status === 'promoted' ? Institution::class : null,
        'canonical_subject_id' => $status === 'promoted' ? $institution->id : null,
    ]);
    if ($status === 'promoted') {
        IdentityResolutionDecision::create([
            'import_row_id' => $row->id,
            'institution_id' => null,
            'decision' => 'approved_new',
            'actor_type' => 'user',
            'actor_id' => 901,
            'reason' => 'Approved new institution for isolated publication test.',
            'decided_at' => now(),
        ]);
    }
    if ($withEvidence) {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Phase 1C-21 multiprocess publication source',
            'url' => 'https://example.test/phase-1c-21-multiprocess',
            'status' => 'active',
        ]);
        $evidence = Evidence::create([
            'source_id' => $source->id,
            'reference' => 'https://example.test/phase-1c-21-multiprocess',
            'excerpt' => 'Official source evidence for concurrent publication validation.',
            'content_hash' => 'phase-1c-21-multiprocess-'.$row->id,
            'observed_at' => now(),
            'status' => 'active',
        ]);
        Assertion::create([
            'evidence_id' => $evidence->id,
            'subject_type' => ImportRow::class,
            'subject_id' => $row->id,
            'attribute' => 'institution_identity',
            'value' => $institution->name,
            'verification_state' => 'observed',
            'confidence' => 1,
        ]);
    }

    return [$row, $institution, $batch];
};

$runPair = function (array $workers, string $prefix) use ($root): array {
    $barrier = sys_get_temp_dir().'/operator-transition-'.bin2hex(random_bytes(8));
    if (! mkdir($barrier, 0700, true) && ! is_dir($barrier)) {
        throw new RuntimeException('Unable to create operator-transition process barrier.');
    }
    $go = $barrier.'/go';
    $processes = [];
    $outputs = [];
    try {
        foreach ($workers as $index => $worker) {
            $ready = $barrier.'/worker-'.$index.'.ready';
            $pipes = [];
            $command = [PHP_BINARY, __FILE__, 'worker', $worker['action'], (string) $worker['row_id'], $ready, $go, (string) $worker['actor_id'], $worker['reason'], (string) ($worker['institution_id'] ?? 0)];
            $environment = null;
            if ($worker['action'] === 'fail-audit-reject') {
                $environment = array_merge(getenv(), ['OPERATOR_HOLD_SIGNAL' => $barrier.'/held']);
            }
            $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root, $environment);
            if (! is_resource($process)) {
                throw new RuntimeException('Unable to start independent operator-transition worker.');
            }
            fclose($pipes[0]);
            $processes[] = [$process, $pipes, $ready];
        }
        $deadline = microtime(true) + 20;
        while (count(array_filter($processes, fn ($entry) => is_file($entry[2]))) !== count($processes) && microtime(true) < $deadline) {
            usleep(20000);
        }
        if (count(array_filter($processes, fn ($entry) => is_file($entry[2]))) !== count($processes)) {
            throw new RuntimeException('All operator-transition workers did not reach the start barrier.');
        }
        file_put_contents($go, 'go');
        foreach ($processes as [$process, $pipes]) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exit = proc_close($process);
            if ($exit !== 0) {
                throw new RuntimeException("{$prefix} worker failed: ".trim((string) $stderr).' '.trim((string) $stdout));
            }
            $decoded = json_decode(trim((string) $stdout), true, 512, JSON_THROW_ON_ERROR);
            $outputs[] = $decoded['result'] ?? null;
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

    return $outputs;
};

$cleanup = function (array $fixtures): void {
    foreach ($fixtures as [$row, $institution, $batch]) {
        $evidenceIds = Assertion::query()->where('subject_type', ImportRow::class)->where('subject_id', $row->id)->pluck('evidence_id');
        $sourceIds = Evidence::query()->whereIn('id', $evidenceIds)->pluck('source_id');
        Assertion::query()->where('subject_type', ImportRow::class)->where('subject_id', $row->id)->delete();
        Evidence::query()->whereIn('id', $evidenceIds)->delete();
        Source::query()->whereIn('id', $sourceIds)->delete();
        ImportBatch::query()->whereKey($batch->id)->delete();
        Institution::query()->whereKey($institution->id)->delete();
    }
};

$fixtures = [];
try {
    // Two identical rejection requests must converge on one decision and one audit event.
    [$row, $institution, $batch] = $createRow();
    $fixtures[] = [$row, $institution, $batch];
    $reason = 'Concurrent identical rejection request.';
    $runPair([
        ['action' => 'reject', 'row_id' => $row->id, 'actor_id' => 902, 'reason' => $reason],
        ['action' => 'reject', 'row_id' => $row->id, 'actor_id' => 902, 'reason' => $reason],
    ], 'identical rejection');
    $decisions = IdentityResolutionDecision::query()->where('import_row_id', $row->id)->count();
    $events = ImportPromotionEvent::query()->where('import_row_id', $row->id)->where('event_type', 'promotion_rejected')->count();
    if ($row->fresh()->status !== 'rejected' || $decisions !== 1 || $events !== 1) {
        throw new RuntimeException("Identical rejection did not converge: status={$row->fresh()->status}, decisions={$decisions}, events={$events}.");
    }

    // A competing approval and rejection must serialize; the final rejected state blocks promotion.
    [$competingRow, $competingInstitution, $competingBatch] = $createRow();
    $fixtures[] = [$competingRow, $competingInstitution, $competingBatch];
    $runPair([
        ['action' => 'reject', 'row_id' => $competingRow->id, 'actor_id' => 903, 'reason' => 'Competing rejection wins state transition.'],
        ['action' => 'decision', 'row_id' => $competingRow->id, 'actor_id' => 904, 'reason' => 'Competing approval attempt.', 'institution_id' => $competingInstitution->id],
    ], 'competing decision');
    if ($competingRow->fresh()->status !== 'rejected'
        || ! IdentityResolutionDecision::query()->where('import_row_id', $competingRow->id)->where('decision', 'rejected')->exists()
        || ! ImportPromotionEvent::query()->where('import_row_id', $competingRow->id)->where('event_type', 'promotion_rejected')->exists()) {
        throw new RuntimeException('Competing decision did not preserve the rejected state and its decision/audit history.');
    }
    if (IdentityResolutionDecision::query()->where('import_row_id', $competingRow->id)->where('decision', 'approved_match')->exists() && $competingRow->fresh()->status !== 'candidate') {
        // An approval that serialized first is retained as immutable history, but the later rejection still blocks promotion.
        if (! IdentityResolutionDecision::query()->where('import_row_id', $competingRow->id)->where('decision', 'rejected')->exists()) {
            throw new RuntimeException('Competing approval history was not followed by a durable rejection decision.');
        }
    }

    // Concurrent state-guard evaluation: two different decision attempts against a row rejected at the same time.
    [$guardRow, $guardInstitution, $guardBatch] = $createRow();
    $fixtures[] = [$guardRow, $guardInstitution, $guardBatch];
    $guardResults = $runPair([
        ['action' => 'guard', 'row_id' => $guardRow->id, 'actor_id' => 906, 'reason' => 'Concurrent state-guard decision.', 'institution_id' => $guardInstitution->id],
        ['action' => 'guard', 'row_id' => $guardRow->id, 'actor_id' => 906, 'reason' => 'Concurrent state-guard decision.', 'institution_id' => $guardInstitution->id],
    ], 'state guard');
    $guardDecisionCount = IdentityResolutionDecision::query()->where('import_row_id', $guardRow->id)->count();
    if ($guardRow->fresh()->status !== 'candidate' || $guardDecisionCount !== 1 || count(array_filter($guardResults, fn ($result) => $result === 'accepted')) !== 2) {
        throw new RuntimeException('Concurrent candidate-state guards did not serialize to one idempotent decision.');
    }

    // Concurrent publication is serialized by the import-row and canonical-institution locks.
    [$publishRow, $publishInstitution, $publishBatch] = $createRow('promoted', true);
    $fixtures[] = [$publishRow, $publishInstitution, $publishBatch];
    $runPair([
        ['action' => 'publish', 'row_id' => $publishRow->id, 'actor_id' => 907, 'reason' => 'Concurrent publication request.'],
        ['action' => 'publish', 'row_id' => $publishRow->id, 'actor_id' => 907, 'reason' => 'Concurrent publication request.'],
    ], 'concurrent publication');
    $publicationEvents = ImportPromotionEvent::query()->where('import_row_id', $publishRow->id)->where('event_type', 'institution_published')->count();
    if ($publishInstitution->fresh()->status !== 'active' || $publicationEvents !== 1) {
        throw new RuntimeException("Concurrent publication did not converge: status={$publishInstitution->fresh()->status}, events={$publicationEvents}.");
    }

    // A failed audit insert rolls back the first attempt while a waiting retry succeeds.
    [$retryRow, $retryInstitution, $retryBatch] = $createRow();
    $fixtures[] = [$retryRow, $retryInstitution, $retryBatch];
    $hold = sys_get_temp_dir().'/operator-transition-held-'.bin2hex(random_bytes(8));
    putenv('OPERATOR_HOLD_SIGNAL='.$hold);
    $firstReady = sys_get_temp_dir().'/operator-transition-first-ready-'.bin2hex(random_bytes(8));
    $go = sys_get_temp_dir().'/operator-transition-first-go-'.bin2hex(random_bytes(8));
    $pipes = [];
    $first = proc_open([PHP_BINARY, __FILE__, 'worker', 'fail-audit-reject', (string) $retryRow->id, $firstReady, $go, '908', 'Retry after injected audit interruption.', '0'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root, array_merge(getenv(), ['OPERATOR_HOLD_SIGNAL' => $hold]));
    if (! is_resource($first)) {
        throw new RuntimeException('Unable to start audit-failure worker.');
    }
    fclose($pipes[0]);
    $deadline = microtime(true) + 20;
    while (! is_file($firstReady) && microtime(true) < $deadline) {
        usleep(20000);
    }
    file_put_contents($go, 'go');
    while (! is_file($hold) && microtime(true) < $deadline) {
        usleep(20000);
    }
    if (! is_file($hold)) {
        throw new RuntimeException('Audit-failure worker did not acquire the row lock.');
    }
    $retryReady = sys_get_temp_dir().'/operator-transition-retry-ready-'.bin2hex(random_bytes(8));
    $retryGo = sys_get_temp_dir().'/operator-transition-retry-go-'.bin2hex(random_bytes(8));
    $retryPipes = [];
    $second = proc_open([PHP_BINARY, __FILE__, 'worker', 'reject', (string) $retryRow->id, $retryReady, $retryGo, '908', 'Retry after injected audit interruption.', '0'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $retryPipes, $root);
    if (! is_resource($second)) {
        throw new RuntimeException('Unable to start waiting rejection retry worker.');
    }
    fclose($retryPipes[0]);
    $deadline = microtime(true) + 20;
    while (! is_file($retryReady) && microtime(true) < $deadline) {
        usleep(20000);
    }
    file_put_contents($retryGo, 'go');
    $firstOut = stream_get_contents($pipes[1]);
    $firstErr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $firstExit = proc_close($first);
    $secondOut = stream_get_contents($retryPipes[1]);
    $secondErr = stream_get_contents($retryPipes[2]);
    fclose($retryPipes[1]);
    fclose($retryPipes[2]);
    $secondExit = proc_close($second);
    @unlink($hold);
    @unlink($firstReady);
    @unlink($go);
    @unlink($retryReady);
    @unlink($retryGo);
    putenv('OPERATOR_HOLD_SIGNAL');
    if ($firstExit !== 0 || $secondExit !== 0 || ! str_contains((string) $firstOut, 'failed_and_rolled_back') || ! str_contains((string) $secondOut, 'rejected')) {
        throw new RuntimeException('Audit failure/retry workers failed: '.$firstErr.' '.$firstOut.' '.$secondErr.' '.$secondOut);
    }
    $retryDecisionCount = IdentityResolutionDecision::query()->where('import_row_id', $retryRow->id)->count();
    $retryEventCount = ImportPromotionEvent::query()->where('import_row_id', $retryRow->id)->where('event_type', 'promotion_rejected')->count();
    if ($retryRow->fresh()->status !== 'rejected' || $retryDecisionCount !== 1 || $retryEventCount !== 1) {
        throw new RuntimeException("Audit failure retry did not converge: status={$retryRow->fresh()->status}, decisions={$retryDecisionCount}, events={$retryEventCount}.");
    }

    fwrite(STDOUT, "PASS: isolated MySQL independent-process tests proved identical rejection replay, competing rejection/decision serialization, concurrent state-guard enforcement, concurrent publication idempotency, and audit-failure rollback followed by a waiting retry with one decision and one event.\n");
} finally {
    $cleanup($fixtures);
}

<?php

declare(strict_types=1);

use App\Models\IdentityMatchCandidate;
use App\Models\ImportBatch;
use App\Models\ImportConflict;
use App\Models\ImportConflictEvent;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Services\InstituteIdentityResolver;
use Illuminate\Contracts\Console\Kernel;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';

if (($argv[1] ?? null) === 'worker') {
    $rowId = (int) ($argv[2] ?? 0);
    $ready = $argv[3] ?? '';
    $go = $argv[4] ?? '';
    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    file_put_contents($ready, 'ready');
    $deadline = microtime(true) + 15;
    while (! is_file($go) && microtime(true) < $deadline) {
        usleep(25000);
    }
    if (! is_file($go)) {
        fwrite(STDERR, "Identity worker did not receive its start barrier.\n");
        exit(2);
    }
    $count = app(InstituteIdentityResolver::class)->resolve(ImportRow::query()->findOrFail($rowId));
    fwrite(STDOUT, json_encode(['matches' => $count], JSON_THROW_ON_ERROR)."\n");
    exit(0);
}

if (getenv('DB_CONNECTION') !== 'mysql') {
    fwrite(STDERR, "Identity conflict concurrency proof requires isolated MySQL.\n");
    exit(2);
}

$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$institution = Institution::create([
    'name' => 'Phase 1C-19 Multiprocess Institute',
    'official_name' => 'Phase 1C-19 Multiprocess Institute',
    'slug' => 'phase-1c-19-multiprocess-institute',
    'status' => 'active',
]);
$batch = ImportBatch::create([
    'batch_key' => 'phase-1c-19-multiprocess-'.bin2hex(random_bytes(5)),
    'entity_type' => 'institution',
    'source_type' => 'manual',
    'status' => 'validated',
    'total_rows' => 2,
    'valid_rows' => 2,
    'candidate_rows' => 2,
    'received_at' => now(),
    'completed_at' => now(),
]);
$row = ImportRow::create([
    'import_batch_id' => $batch->id,
    'row_number' => 1,
    'status' => 'candidate',
    'match_state' => 'new_candidate',
    'payload' => ['name' => $institution->name, 'slug' => $institution->slug],
    'normalized_payload' => ['name' => $institution->name, 'official_name' => $institution->official_name, 'slug' => $institution->slug],
]);
ImportRow::create([
    'import_batch_id' => $batch->id,
    'row_number' => 2,
    'status' => 'candidate',
    'match_state' => 'new_candidate',
    'payload' => ['name' => 'Second staged identity', 'slug' => $institution->slug],
    'normalized_payload' => ['name' => 'Second staged identity', 'slug' => $institution->slug],
]);

$barrier = sys_get_temp_dir().'/identity-conflict-'.bin2hex(random_bytes(8));
if (! mkdir($barrier, 0700, true) && ! is_dir($barrier)) {
    throw new RuntimeException('Unable to create identity concurrency barrier.');
}
$go = $barrier.'/go';
$workers = [];

try {
    foreach ([1, 2] as $number) {
        $ready = $barrier.'/worker-'.$number.'.ready';
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, __FILE__, 'worker', (string) $row->id, $ready, $go],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root
        );
        if (! is_resource($process)) {
            throw new RuntimeException('Unable to start identity conflict worker.');
        }
        fclose($pipes[0]);
        $workers[] = [$process, $pipes, $ready];
    }

    $deadline = microtime(true) + 15;
    while ((! is_file($workers[0][2]) || ! is_file($workers[1][2])) && microtime(true) < $deadline) {
        usleep(25000);
    }
    if (! is_file($workers[0][2]) || ! is_file($workers[1][2])) {
        throw new RuntimeException('Both identity conflict workers did not reach the barrier.');
    }
    file_put_contents($go, 'go');

    foreach ($workers as [$process, $pipes]) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit !== 0) {
            throw new RuntimeException('Identity conflict worker failed: '.trim((string) $stderr).' '.trim((string) $stdout));
        }
    }

    $matches = IdentityMatchCandidate::query()->where('import_row_id', $row->id)->count();
    $conflicts = ImportConflict::query()->where('import_row_id', $row->id)->count();
    $events = ImportConflictEvent::query()->where('event_type', 'detected')->count();
    if ($matches !== 1 || $conflicts !== 1 || $events !== 1 || $row->fresh()->match_state !== 'conflict') {
        throw new RuntimeException("Concurrent identity resolution did not converge: matches={$matches}, conflicts={$conflicts}, detected_events={$events}, state=".$row->fresh()->match_state);
    }

    fwrite(STDOUT, "PASS: two independent PHP processes converged on one identity match, one open conflict, and one detection event; the row remained blocked in conflict state.\n");
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
    ImportBatch::query()->whereKey($batch->id)->delete();
    Institution::query()->whereKey($institution->id)->delete();
    foreach (glob($barrier.'/*') ?: [] as $path) {
        @unlink($path);
    }
    @rmdir($barrier);
}

<?php

declare(strict_types=1);

use App\Services\ExecutionLeaseService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';

if (($argv[1] ?? null) === 'worker') {
    $taskKey = $argv[2] ?? '';
    $marker = $argv[3] ?? '';
    $holdSeconds = (int) ($argv[4] ?? 0);

    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();

    $result = app(ExecutionLeaseService::class)->run($taskKey, function () use ($marker, $holdSeconds): string {
        if ($marker !== '') {
            file_put_contents($marker, 'lease-acquired');
        }

        if ($holdSeconds > 0) {
            sleep($holdSeconds);
        }

        return 'acquired';
    });

    fwrite(STDOUT, $result === null ? "REJECTED\n" : "ACQUIRED\n");
    exit(0);
}

if (getenv('DB_CONNECTION') !== 'mysql') {
    fwrite(STDERR, "This concurrency proof must run against the isolated MySQL service.\n");
    exit(2);
}

$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$taskKey = 'ci-multiprocess-lease-'.(string) Str::uuid();
$marker = sys_get_temp_dir().'/execution-lease-'.bin2hex(random_bytes(8));
$rootDirectory = $root;

$start = static function (array $arguments) use ($rootDirectory): array {
    $pipes = [];
    $process = proc_open(
        array_merge([PHP_BINARY, __FILE__], $arguments),
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $rootDirectory
    );

    if (! is_resource($process)) {
        throw new RuntimeException('Unable to start an independent PHP worker process.');
    }

    fclose($pipes[0]);

    return [$process, $pipes];
};

try {
    [$first, $firstPipes] = $start(['worker', $taskKey, $marker, '4']);

    $deadline = microtime(true) + 10;
    while (! is_file($marker) && microtime(true) < $deadline) {
        usleep(50000);
    }

    if (! is_file($marker)) {
        throw new RuntimeException('First worker did not publish its lease-acquired barrier.');
    }

    [$second, $secondPipes] = $start(['worker', $taskKey, '', '0']);

    $secondOutput = stream_get_contents($secondPipes[1]);
    $secondError = stream_get_contents($secondPipes[2]);
    fclose($secondPipes[1]);
    fclose($secondPipes[2]);
    $secondExit = proc_close($second);

    $firstOutput = stream_get_contents($firstPipes[1]);
    $firstError = stream_get_contents($firstPipes[2]);
    fclose($firstPipes[1]);
    fclose($firstPipes[2]);
    $firstExit = proc_close($first);

    if ($firstExit !== 0 || trim($firstOutput) !== 'ACQUIRED') {
        throw new RuntimeException('First worker failed: '.trim($firstError).' '.trim($firstOutput));
    }

    if ($secondExit !== 0 || trim($secondOutput) !== 'REJECTED') {
        throw new RuntimeException('Second independent worker was not rejected: '.trim($secondError).' '.trim($secondOutput));
    }

    $runCount = DB::table('governed_execution_runs')->where('task_key', $taskKey)->count();
    if ($runCount !== 1) {
        throw new RuntimeException("Expected exactly one run-ledger row; found {$runCount}.");
    }

    fwrite(STDOUT, "PASS: two independent PHP processes contended on MySQL; one acquired the lease, one was rejected, and exactly one run was recorded.\n");
} finally {
    if (is_file($marker)) {
        unlink($marker);
    }
}

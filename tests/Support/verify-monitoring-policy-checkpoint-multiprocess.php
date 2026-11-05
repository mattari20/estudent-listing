<?php

declare(strict_types=1);

use App\Models\SourceMonitoringPolicy;
use App\Services\MonitoringPolicyCheckpointService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';

if (($argv[1] ?? null) === 'worker') {
    $policyId = (int) ($argv[2] ?? 0);
    $marker = $argv[3] ?? '';
    $holdSeconds = (int) ($argv[4] ?? 0);
    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();

    try {
        $runId = app(MonitoringPolicyCheckpointService::class)->startPolicyRun($policyId, null);
        if ($marker !== '') {
            file_put_contents($marker, (string) $runId);
        }
        if ($holdSeconds > 0) {
            sleep($holdSeconds);
        }
        DB::table('monitoring_policy_runs')->where('id', $runId)->update([
            'status' => 'completed',
            'finished_at' => now(),
            'updated_at' => now(),
        ]);
        fwrite(STDOUT, "STARTED:{$runId}\n");
        exit(0);
    } catch (RuntimeException $exception) {
        if ($exception->getMessage() === 'A monitoring policy run is already active.') {
            fwrite(STDOUT, "REJECTED\n");
            exit(0);
        }
        fwrite(STDERR, $exception->getMessage()."\n");
        exit(1);
    }
}

if (getenv('DB_CONNECTION') !== 'mysql') {
    fwrite(STDERR, "This concurrency proof must run against the isolated MySQL service.\n");
    exit(2);
}

$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$policy = SourceMonitoringPolicy::create([
    'name' => 'CI direct-caller concurrency '.bin2hex(random_bytes(4)),
    'fresh_after_days' => 14,
    'stale_after_days' => 30,
    'priority' => 'normal',
    'status' => 'active',
    'cadence' => 'daily',
    'consecutive_failures' => 0,
]);
$marker = sys_get_temp_dir().'/monitoring-policy-checkpoint-'.bin2hex(random_bytes(8));

$start = static function (array $arguments) use ($root): array {
    $pipes = [];
    $process = proc_open(
        array_merge([PHP_BINARY, __FILE__], $arguments),
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $root
    );
    if (! is_resource($process)) {
        throw new RuntimeException('Unable to start an independent monitoring policy worker.');
    }
    fclose($pipes[0]);
    return [$process, $pipes];
};

$workers = [];
try {
    [$first, $firstPipes] = $start(['worker', (string) $policy->id, $marker, '4']);
    $workers[] = [$first, $firstPipes];

    $deadline = microtime(true) + 10;
    while (! is_file($marker) && microtime(true) < $deadline) {
        usleep(50000);
    }
    if (! is_file($marker)) {
        throw new RuntimeException('First worker did not establish its running policy checkpoint.');
    }

    [$second, $secondPipes] = $start(['worker', (string) $policy->id, '', '0']);
    $workers[] = [$second, $secondPipes];

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

    if ($firstExit !== 0 || ! str_starts_with(trim($firstOutput), 'STARTED:')) {
        throw new RuntimeException('First policy worker failed: '.trim($firstError).' '.trim($firstOutput));
    }
    if ($secondExit !== 0 || trim($secondOutput) !== 'REJECTED') {
        throw new RuntimeException('Second direct caller was not rejected: '.trim($secondError).' '.trim($secondOutput));
    }

    $runs = DB::table('monitoring_policy_runs')->where('source_monitoring_policy_id', $policy->id)->get();
    if ($runs->count() !== 1 || $runs->first()->status !== 'completed') {
        throw new RuntimeException('Expected exactly one completed policy run after direct-caller contention.');
    }

    fwrite(STDOUT, "PASS: independent PHP processes contended on one policy checkpoint in isolated MySQL; one started, the second was rejected, and exactly one run was persisted.\n");
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
    if (is_file($marker)) {
        @unlink($marker);
    }
    DB::table('monitoring_policy_runs')->where('source_monitoring_policy_id', $policy->id)->delete();
    $policy->delete();
}

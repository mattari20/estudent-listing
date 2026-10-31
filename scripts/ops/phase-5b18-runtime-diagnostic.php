<?php
declare(strict_types=1);

/**
 * Read-only Phase 5B.18 production runtime diagnostic.
 * This script never invokes the qualification command or performs source requests.
 */

const REPORT_LIMIT_BYTES = 262144;

$base = getcwd() ?: '';
$target = getenv('PHASE_5B18_TARGET_REVISION') ?: '';
$repo = getenv('PHASE_5B18_PARITY_REPO') ?: '';
$reportPath = null;
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--report=')) {
        $reportPath = substr($argument, strlen('--report='));
    }
}

$report = [
    'schema_version' => 2,
    'phase' => '5B.18',
    'checked_at_utc' => gmdate(DATE_ATOM),
    'overall_status' => 'FAIL',
    'expected_source_revision' => $target !== '' ? $target : null,
    'mutation_performed' => false,
    'source_requests_performed' => false,
    'qualification_command_executed' => false,
    'php' => [
        'binary' => PHP_BINARY,
        'version' => PHP_VERSION,
        'sapi' => PHP_SAPI,
        'working_directory' => $base,
        'artisan_entrypoint' => realpath($base . '/artisan') ?: $base . '/artisan',
    ],
    'laravel' => [],
    'artisan' => [],
    'command' => [],
    'files' => [],
    'autoload' => [],
    'bootstrap_cache' => [],
    'parity' => [],
    'steps' => [],
    'errors' => [],
];

$step = static function (string $name, callable $work) use (&$report): mixed {
    try {
        $value = $work();
        $report['steps'][$name] = 'PASS';
        return $value;
    } catch (Throwable $exception) {
        $report['steps'][$name] = 'FAIL';
        $report['errors'][] = [
            'step' => $name,
            'exception_class' => get_class($exception),
        ];
        return null;
    }
};

$sha256 = static function (string $path): ?string {
    return is_file($path) ? hash_file('sha256', $path) : null;
};

$step('source_hashes', static function () use ($base, $repo, $target, $sha256, &$report): void {
    if ($repo === '' || $target === '') {
        throw new RuntimeException('expected_revision_context_unavailable');
    }
    foreach (['artisan', 'bootstrap/app.php', 'app/Console/Commands/QualifyOfficialInstituteSources.php'] as $path) {
        $expected = shell_exec('git -C ' . escapeshellarg($repo) . ' show ' . escapeshellarg($target . ':' . $path) . ' 2>/dev/null');
        $actualPath = $base . '/' . $path;
        $actualHash = $sha256($actualPath);
        $expectedHash = is_string($expected) ? hash('sha256', $expected) : null;
        $report['files'][$path] = [
            'actual_sha256' => $actualHash,
            'expected_sha256' => $expectedHash,
            'matches' => $expectedHash !== null && $actualHash !== null && hash_equals($expectedHash, $actualHash),
            'realpath' => realpath($actualPath) ?: null,
        ];
    }
});

$step('composer_autoload', static function () use ($base, $sha256, &$report): void {
    $loaderPath = $base . '/vendor/autoload.php';
    if (!is_file($loaderPath)) {
        throw new RuntimeException('composer_autoload_missing');
    }
    $loader = require_once $loaderPath;
    $class = 'App\\Console\\Commands\\QualifyOfficialInstituteSources';
    $resolved = method_exists($loader, 'findFile') ? $loader->findFile($class) : false;
    $metadata = [];
    foreach ([
        'vendor/autoload.php',
        'vendor/composer/autoload_real.php',
        'vendor/composer/autoload_psr4.php',
        'vendor/composer/autoload_classmap.php',
        'vendor/composer/autoload_static.php',
    ] as $path) {
        $metadata[$path] = [
            'exists' => is_file($base . '/' . $path),
            'sha256' => $sha256($base . '/' . $path),
        ];
    }
    $report['autoload'] = [
        'loader_class' => get_class($loader),
        'class_resolved' => is_string($resolved) && $resolved !== '',
        'resolved_file' => is_string($resolved) ? $resolved : null,
        'resolved_sha256' => is_string($resolved) ? $sha256($resolved) : null,
        'metadata_files' => $metadata,
    ];
});

$step('bootstrap_and_command_registry', static function () use ($base, &$report): void {
    require_once $base . '/vendor/autoload.php';
    $app = require $base . '/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $report['laravel'] = [
        'version' => $app->version(),
        'application_class' => get_class($app),
        'base_path' => $app->basePath(),
        'bootstrap_success' => true,
    ];

    $commands = \Illuminate\Support\Facades\Artisan::all();
    $names = array_keys($commands);
    sort($names, SORT_STRING);
    $command = $commands['institutes:qualify-official-sources'] ?? null;
    $report['artisan'] = [
        'registered_command_names' => $names,
        'registered_count' => count($names),
        'qualification_command_registered' => $command !== null,
    ];

    if ($command instanceof \Illuminate\Console\Command) {
        $reflection = new ReflectionClass($command);
        $definition = $command->getDefinition();
        $options = [];
        foreach ($definition->getOptions() as $option) {
            $options[] = '--' . $option->getName();
        }
        sort($options, SORT_STRING);
        $file = $reflection->getFileName() ?: null;
        $report['command'] = [
            'class' => get_class($command),
            'reflection_source_file' => $file,
            'reflection_source_realpath' => $file ? (realpath($file) ?: $file) : null,
            'signature' => $definition->getSynopsis(),
            'options' => $options,
            'source_option_present' => in_array('--source', $options, true),
            'reflection_file_sha256' => $file ? hash_file('sha256', $file) : null,
        ];
    }
});

$step('native_artisan_inspection', static function () use ($base, &$report): void {
    $php = getenv('PHASE_5B18_NATIVE_PHP') ?: PHP_BINARY;
    $artisan = getenv('PHASE_5B18_ARTISAN_PATH') ?: ($base . '/artisan');
    if (!is_file($artisan) || !is_executable($php)) {
        throw new RuntimeException('native_artisan_runtime_unavailable');
    }

    $sanitize = static function (array $lines): array {
        return array_map(static function ($line): string {
            $line = (string) $line;
            $line = preg_replace('/(?i)\b(password|secret|token|authorization|api[_-]?key)\b(\s*[:=]\s*)[^\s,;]+/', '$1$2[REDACTED]', $line) ?? '[REDACTED_LINE]';
            $line = preg_replace('/(?i)\bBearer\s+[A-Za-z0-9._~+\/-]+=*/', 'Bearer [REDACTED]', $line) ?? '[REDACTED_LINE]';
            return substr($line, 0, 1000);
        }, $lines);
    };

    $listOutput = [];
    $listCode = 0;
    exec(escapeshellarg($php) . ' ' . escapeshellarg($artisan) . ' list --raw --no-ansi 2>&1', $listOutput, $listCode);
    $listMatch = false;
    foreach ($listOutput as $line) {
        if (preg_match('/^institutes:qualify-official-sources(?:\s|$)/', (string) $line) === 1) {
            $listMatch = true;
            break;
        }
    }

    $helpOutput = [];
    $helpCode = 0;
    exec(escapeshellarg($php) . ' ' . escapeshellarg($artisan) . ' help institutes:qualify-official-sources --no-ansi 2>&1', $helpOutput, $helpCode);
    $helpText = implode("\n", $helpOutput);
    $sourcePresent = preg_match('/(?:^|\s)--source(?:[=\s]|$)/m', $helpText) === 1;

    $report['artisan']['native_list'] = [
        'command' => 'list --raw --no-ansi',
        'exit_code' => $listCode,
        'qualification_command_listed' => $listMatch,
        'output_excerpt' => $sanitize(array_slice($listOutput, 0, 30)),
        'output_truncated' => count($listOutput) > 30,
    ];
    $report['artisan']['native_help'] = [
        'command' => 'help institutes:qualify-official-sources --no-ansi',
        'exit_code' => $helpCode,
        'command_help_succeeded' => $helpCode === 0,
        'source_option_present' => $sourcePresent,
        'options_excerpt' => $sanitize(array_slice($helpOutput, 0, 80)),
        'output_truncated' => count($helpOutput) > 80,
    ];
    if ($listCode !== 0 || $helpCode !== 0 || !$listMatch) {
        throw new RuntimeException('native_artisan_list_or_help_failed');
    }
});
$step('bootstrap_cache_inventory', static function () use ($base, $sha256, &$report): void {
    $root = $base . '/bootstrap/cache';
    $files = [];
    foreach (glob($root . '/*') ?: [] as $path) {
        if (!is_file($path)) {
            continue;
        }
        $files[] = [
            'name' => basename($path),
            'size_bytes' => filesize($path),
            'modified_at_utc' => gmdate(DATE_ATOM, filemtime($path)),
            'sha256' => $sha256($path),
        ];
    }
    $report['bootstrap_cache'] = [
        'directory_exists' => is_dir($root),
        'file_count' => count($files),
        'files' => array_slice($files, 0, 100),
    ];
});

$step('two_way_file_parity', static function () use ($base, $repo, $target, $sha256, &$report): void {
    if ($repo === '' || $target === '') {
        throw new RuntimeException('expected_revision_context_unavailable');
    }
    $paths = [];
    exec(
        'git -C ' . escapeshellarg($repo) . ' ls-tree -r --name-only ' . escapeshellarg($target)
        . ' -- app bootstrap config database resources routes composer.json composer.lock public artisan 2>/dev/null',
        $paths,
        $exitCode
    );
    if ($exitCode !== 0) {
        throw new RuntimeException('target_file_list_unavailable');
    }
    $expected = array_values(array_filter($paths, static fn ($path): bool => $path !== ''));
    $missing = [];
    $different = [];
    foreach ($expected as $relative) {
        $actualPath = $base . '/' . $relative;
        if (!is_file($actualPath) && !is_link($actualPath)) {
            $missing[] = $relative;
            continue;
        }
        $blob = shell_exec('git -C ' . escapeshellarg($repo) . ' show ' . escapeshellarg($target . ':' . $relative) . ' 2>/dev/null');
        $expectedHash = is_string($blob) ? hash('sha256', $blob) : null;
        $actualHash = $sha256($actualPath);
        if ($expectedHash === null || $actualHash === null || $expectedHash !== $actualHash) {
            $different[] = [
                'path' => $relative,
                'expected_sha256' => $expectedHash,
                'actual_sha256' => $actualHash,
            ];
        }
    }

    $extras = [];
    $roots = ['app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'public'];
    $generated = ['bootstrap/cache', 'public/build', 'public/hot'];
    foreach ($roots as $rootName) {
        $directory = $base . '/' . $rootName;
        if (!is_dir($directory)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $relative = substr($file->getPathname(), strlen($base) + 1);
            if (in_array($relative, $expected, true)) {
                continue;
            }
            $skip = false;
            foreach ($generated as $prefix) {
                if ($relative === $prefix || str_starts_with($relative, $prefix . '/')) {
                    $skip = true;
                    break;
                }
            }
            if (!$skip && ($file->isFile() || $file->isLink())) {
                $extras[] = ['path' => $relative, 'sha256' => $sha256($file->getPathname())];
                if (count($extras) >= 300) {
                    break 2;
                }
            }
        }
    }

    $report['parity'] = [
        'expected_file_count' => count($expected),
        'missing_expected_files' => array_slice($missing, 0, 300),
        'different_expected_files' => array_slice($different, 0, 300),
        'unexpected_extra_files' => $extras,
        'tracked_parity_complete' => $missing === [] && $different === [] && $extras === [],
    ];
});

$step('runtime_inventory', static function () use ($base, $sha256, &$report): void {
    $inventory = [];
    foreach (['app/Console', 'bootstrap', 'config', 'vendor/composer'] as $root) {
        $directory = $base . '/' . $root;
        if (!is_dir($directory)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $inventory[] = [
                'path' => substr($file->getPathname(), strlen($base) + 1),
                'sha256' => $sha256($file->getPathname()),
                'size_bytes' => $file->getSize(),
            ];
            if (count($inventory) >= 500) {
                break 2;
            }
        }
    }
    $report['files']['runtime_inventory'] = $inventory;
});

$requiredSteps = ['source_hashes', 'composer_autoload', 'bootstrap_and_command_registry', 'native_artisan_inspection', 'bootstrap_cache_inventory', 'two_way_file_parity', 'runtime_inventory'];
$report['overall_status'] = 'PASS';
foreach ($requiredSteps as $requiredStep) {
    if (($report['steps'][$requiredStep] ?? 'FAIL') !== 'PASS') {
        $report['overall_status'] = 'FAIL';
    }
}

$encode = static function (array $payload): string {
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_string($json)) {
        throw new RuntimeException('json_encoding_failed_' . json_last_error());
    }
    return $json . PHP_EOL;
};

if ($reportPath === null || $reportPath === '') {
    fwrite(STDERR, "ERROR: report output argument missing\n");
    exit(64);
}
if (file_exists($reportPath)) {
    fwrite(STDERR, "ERROR: refusing to overwrite report\n");
    exit(65);
}

try {
    $json = $encode($report);
} catch (Throwable $exception) {
    $report['errors'][] = ['step' => 'json_encoding', 'exception_class' => get_class($exception)];
    $report['overall_status'] = 'FAIL';
    $json = json_encode([
        'schema_version' => 2,
        'phase' => '5B.18',
        'overall_status' => 'FAIL',
        'mutation_performed' => false,
        'source_requests_performed' => false,
        'qualification_command_executed' => false,
        'steps' => $report['steps'],
        'errors' => $report['errors'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $json = (is_string($json) ? $json : '{}') . PHP_EOL;
}

if (strlen($json) > REPORT_LIMIT_BYTES) {
    $report['errors'][] = ['step' => 'report_size', 'exception_class' => 'ReportLimitExceeded'];
    $report['overall_status'] = 'FAIL';
    $report['artisan'] = [];
    $report['files']['runtime_inventory'] = [];
    $report['parity']['missing_expected_files'] = array_slice($report['parity']['missing_expected_files'] ?? [], 0, 50);
    $report['parity']['different_expected_files'] = array_slice($report['parity']['different_expected_files'] ?? [], 0, 50);
    $report['parity']['unexpected_extra_files'] = array_slice($report['parity']['unexpected_extra_files'] ?? [], 0, 50);
    $json = $encode($report);
}
if (strlen($json) > REPORT_LIMIT_BYTES) {
    $json = json_encode([
        'schema_version' => 2,
        'phase' => '5B.18',
        'overall_status' => 'FAIL',
        'error' => 'ReportLimitExceeded',
        'mutation_performed' => false,
        'source_requests_performed' => false,
        'qualification_command_executed' => false,
        'steps' => $report['steps'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

$temporary = $reportPath . '.tmp.' . getmypid();
$handle = @fopen($temporary, 'x');
if ($handle === false) {
    fwrite(STDERR, "ERROR: cannot create exclusive temporary report\n");
    exit(66);
}
@chmod($temporary, 0600);
$written = fwrite($handle, $json);
$flushed = fflush($handle);
fclose($handle);
if ($written !== strlen($json) || !$flushed || !@chmod($temporary, 0600)) {
    @unlink($temporary);
    fwrite(STDERR, "ERROR: partial report write failed\n");
    exit(67);
}
if (file_exists($reportPath)) {
    @unlink($temporary);
    fwrite(STDERR, "ERROR: refusing report replacement\n");
    exit(65);
}
if (!@rename($temporary, $reportPath)) {
    @unlink($temporary);
    fwrite(STDERR, "ERROR: atomic report rename failed\n");
    exit(68);
}
@chmod($reportPath, 0600);
exit($report['overall_status'] === 'PASS' ? 0 : 2);

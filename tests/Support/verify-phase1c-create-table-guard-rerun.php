<?php

declare(strict_types=1);

use Illuminate\\Contracts\\Console\\Kernel;
use Illuminate\\Support\\Facades\\DB;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (DB::getDriverName() !== 'mysql') {
    fwrite(STDERR, "This migration rerun proof requires the isolated MySQL/MariaDB service.\\n");
    exit(2);
}

$migrations = [
    '2026_10_17_000001_create_password_reset_tokens_table.php',
    '2026_10_25_000001_create_structured_enrichment_promotion_events_table.php',
    '2026_11_03_000001_create_governed_execution_leases_table.php',
    '2026_11_03_000002_create_governed_execution_runs_table.php',
    '2026_11_04_000012_add_identity_conflict_persistence_keys_and_events.php',
    '2026_11_04_000013_create_source_snapshot_reconciliation_requests_table.php',
];

foreach ($migrations as $file) {
    $migration = require $root.'/database/migrations/'.$file;
    $migration->up();
    fwrite(STDOUT, 'rerun_definition_check='.$file.':PASS'.PHP_EOL);
}

fwrite(STDOUT, "Verified exact existing column contracts for six guarded table migrations on the isolated database.\\n");

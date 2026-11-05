<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (DB::getDriverName() !== 'mysql') {
    fwrite(STDERR, "This trigger lifecycle proof requires the isolated MySQL service.\n");
    exit(2);
}

$tables = [
    'security_audit_events',
    'verification_decisions',
    'provenance_review_events',
    'import_promotion_decisions',
    'import_promotion_events',
    'reverification_events',
    'structured_enrichment_promotion_events',
    'official_source_refresh_events',
    'import_conflict_events',
    'import_reconciliation_events',
    'governed_execution_run_events',
];

$expected = [];
foreach ($tables as $table) {
    $suffix = substr(hash('sha256', $table), 0, 16);
    $expected[] = 'audit_immut_'.$suffix.'_u';
    $expected[] = 'audit_immut_'.$suffix.'_d';
}
$expected[] = 'identity_decision_update_guard';
$expected[] = 'identity_decision_delete_guard';

$assertTriggerSet = static function (bool $shouldExist) use ($expected): void {
    $existing = DB::table('information_schema.TRIGGERS')
        ->where('TRIGGER_SCHEMA', DB::getDatabaseName())
        ->pluck('TRIGGER_NAME')
        ->all();
    $found = array_values(array_intersect($expected, $existing));
    if ($shouldExist && count($found) !== count($expected)) {
        $missing = array_values(array_diff($expected, $found));
        throw new RuntimeException('Expected MySQL audit triggers are missing: '.implode(', ', $missing));
    }
    if (! $shouldExist && $found !== []) {
        throw new RuntimeException('MySQL audit triggers remain after migration rollback: '.implode(', ', $found));
    }
};

$migration = require $root.'/database/migrations/2026_11_04_000014_enforce_append_only_audit_retention.php';

$assertTriggerSet(true);
$migration->down();
$assertTriggerSet(false);
$migration->up();
$assertTriggerSet(true);

$method = (new ReflectionClass($migration))->getMethod('installMysqlGuards');
$method->setAccessible(true);
$method->invoke($migration);
$assertTriggerSet(true);

fwrite(STDOUT, "Verified 24 named MySQL audit triggers, rollback removal, reinstallation and idempotent repeated guard installation.\n");

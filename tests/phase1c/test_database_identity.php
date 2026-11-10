<?php
declare(strict_types=1);

require_once __DIR__ . '/../../scripts/phase1c/readonly-schema-diagnostic.php';

$expected = 'u537436044_Institutes';
$cases = [
    'correct' => static function () use ($expected): void { phase1cValidateDatabaseIdentity($expected, $expected); },
    'missing_actual' => static function () use ($expected): void { phase1cValidateDatabaseIdentity(null, $expected); },
    'missing_expected' => static function (): void { phase1cValidateDatabaseIdentity('u537436044_Institutes', ''); },
    'incorrect' => static function () use ($expected): void { phase1cValidateDatabaseIdentity('unexpected_database', $expected); },
];
foreach ($cases as $name => $case) {
    $failed = false;
    try { $case(); } catch (RuntimeException $e) { $failed = true; }
    $shouldFail = $name !== 'correct';
    if ($failed !== $shouldFail) {
        fwrite(STDERR, "database_identity_test=FAIL case={$name}\n");
        exit(1);
    }
    echo "database_identity_test=PASS case={$name}\n";
}

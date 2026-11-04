<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const APPEND_ONLY_TABLES = [
        'security_audit_events',
        'change_events',
        'identity_resolution_decisions',
        'verification_decisions',
        'provenance_review_events',
        'import_reconciliation_events',
        'import_promotion_decisions',
        'import_promotion_events',
        'reverification_events',
        'structured_enrichment_promotion_events',
        'official_source_refresh_events',
        'import_conflict_events',
    ];

    private const PARENT_GUARDS = [
        'import_rows' => [
            'EXISTS (SELECT 1 FROM identity_resolution_decisions WHERE import_row_id = OLD.id)',
            'EXISTS (SELECT 1 FROM import_promotion_decisions WHERE import_row_id = OLD.id)',
            'EXISTS (SELECT 1 FROM import_promotion_events WHERE import_row_id = OLD.id)',
            'EXISTS (SELECT 1 FROM import_conflicts c INNER JOIN import_conflict_events e ON e.import_conflict_id = c.id WHERE c.import_row_id = OLD.id)',
        ],
        'import_batches' => [
            'EXISTS (SELECT 1 FROM import_promotion_events WHERE import_batch_id = OLD.id)',
            'EXISTS (SELECT 1 FROM import_reconciliation_events WHERE import_batch_id = OLD.id)',
            'EXISTS (SELECT 1 FROM structured_enrichment_promotion_events WHERE import_batch_id = OLD.id)',
            'EXISTS (SELECT 1 FROM import_rows r INNER JOIN identity_resolution_decisions d ON d.import_row_id = r.id WHERE r.import_batch_id = OLD.id)',
            'EXISTS (SELECT 1 FROM import_rows r INNER JOIN import_promotion_decisions d ON d.import_row_id = r.id WHERE r.import_batch_id = OLD.id)',
            'EXISTS (SELECT 1 FROM import_rows r INNER JOIN import_conflicts c ON c.import_row_id = r.id INNER JOIN import_conflict_events e ON e.import_conflict_id = c.id WHERE r.import_batch_id = OLD.id)',
        ],
        'assertions' => [
            'EXISTS (SELECT 1 FROM verification_decisions WHERE assertion_id = OLD.id)',
        ],
        'data_reverification_queue' => [
            'EXISTS (SELECT 1 FROM reverification_events WHERE queue_id = OLD.id)',
        ],
        'enrichment_import_rows' => [
            'EXISTS (SELECT 1 FROM structured_enrichment_promotion_events WHERE enrichment_import_row_id = OLD.id)',
        ],
        'import_conflicts' => [
            'EXISTS (SELECT 1 FROM import_conflict_events WHERE import_conflict_id = OLD.id)',
        ],
    ];

    public function up(): void
    {
        $driver = DB::getDriverName();
        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('Append-only audit retention requires a supported MySQL or SQLite trigger driver.');
        }

        foreach (self::APPEND_ONLY_TABLES as $table) {
            $this->createImmutableTriggers($driver, $table);
        }

        foreach (self::PARENT_GUARDS as $table => $conditions) {
            $this->createParentGuard($driver, $table, $conditions);
        }
    }

    private function createImmutableTriggers(string $driver, string $table): void
    {
        $suffix = substr(hash('sha256', $table), 0, 16);
        $message = 'Append-only audit history cannot be changed or deleted';

        foreach (['UPDATE' => 'u', 'DELETE' => 'd'] as $operation => $short) {
            $name = 'audit_immut_'.$suffix.'_'.$short;

            if ($driver === 'mysql') {
                DB::unprepared(
                    'CREATE TRIGGER '.$name.' BEFORE '.$operation.' ON '.$table.' FOR EACH ROW '
                    ."SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '".$message."'"
                );
            } else {
                DB::unprepared(
                    'CREATE TRIGGER IF NOT EXISTS '.$name.' BEFORE '.$operation.' ON '.$table.' '
                    ."BEGIN SELECT RAISE(ABORT, '".$message."'); END"
                );
            }
        }
    }

    private function createParentGuard(string $driver, string $table, array $conditions): void
    {
        $name = 'audit_parent_guard_'.substr(hash('sha256', $table), 0, 16);
        $condition = implode(' OR ', $conditions);
        $message = 'Parent deletion would remove retained audit history';

        if ($driver === 'mysql') {
            DB::unprepared(
                'CREATE TRIGGER '.$name.' BEFORE DELETE ON '.$table.' FOR EACH ROW '
                .'BEGIN IF '.$condition." THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '".$message."'; END IF; END"
            );
        } else {
            DB::unprepared(
                'CREATE TRIGGER IF NOT EXISTS '.$name.' BEFORE DELETE ON '.$table.' '
                .'WHEN '.$condition." BEGIN SELECT RAISE(ABORT, '".$message."'); END"
            );
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        foreach (self::APPEND_ONLY_TABLES as $table) {
            $suffix = substr(hash('sha256', $table), 0, 16);
            foreach (['u', 'd'] as $short) {
                $name = 'audit_immut_'.$suffix.'_'.$short;
                DB::unprepared('DROP TRIGGER IF EXISTS '.$name);
            }
        }

        foreach (array_keys(self::PARENT_GUARDS) as $table) {
            $name = 'audit_parent_guard_'.substr(hash('sha256', $table), 0, 16);
            DB::unprepared('DROP TRIGGER IF EXISTS '.$name);
        }
    }
};

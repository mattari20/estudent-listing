<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SQLITE_APPEND_ONLY_TABLES = [
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

    private const MYSQL_APPEND_ONLY_TABLES = [
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

    private const SQLITE_PARENT_GUARDS = [
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
        'governed_execution_runs' => [
            'EXISTS (SELECT 1 FROM governed_execution_run_events WHERE run_id = OLD.id)',
        ],
    ];

    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $this->installSqliteGuards();
            return;
        }

        if ($driver !== 'mysql') {
            throw new RuntimeException('Audit retention requires MySQL or SQLite.');
        }

        $orphans = [
            'identity decisions without import rows' => DB::table('identity_resolution_decisions as d')
                ->leftJoin('import_rows as r', 'r.id', '=', 'd.import_row_id')->whereNull('r.id')->exists(),
            'verification decisions without assertions' => DB::table('verification_decisions as d')
                ->leftJoin('assertions as a', 'a.id', '=', 'd.assertion_id')->whereNull('a.id')->exists(),
            'reconciliation events without batches' => DB::table('import_reconciliation_events as e')
                ->leftJoin('import_batches as b', 'b.id', '=', 'e.import_batch_id')->whereNull('b.id')->exists(),
            'promotion decisions without rows' => DB::table('import_promotion_decisions as d')
                ->leftJoin('import_rows as r', 'r.id', '=', 'd.import_row_id')->whereNull('r.id')->exists(),
            'promotion events without rows' => DB::table('import_promotion_events as e')
                ->leftJoin('import_rows as r', 'r.id', '=', 'e.import_row_id')->whereNull('r.id')->exists(),
            'promotion events without batches' => DB::table('import_promotion_events as e')
                ->leftJoin('import_batches as b', 'b.id', '=', 'e.import_batch_id')->whereNull('b.id')->exists(),
            'reverification events without queue rows' => DB::table('reverification_events as e')
                ->leftJoin('data_reverification_queue as q', 'q.id', '=', 'e.queue_id')->whereNull('q.id')->exists(),
            'structured enrichment events without rows' => DB::table('structured_enrichment_promotion_events as e')
                ->leftJoin('enrichment_import_rows as r', 'r.id', '=', 'e.enrichment_import_row_id')->whereNull('r.id')->exists(),
            'structured enrichment events without batches' => DB::table('structured_enrichment_promotion_events as e')
                ->leftJoin('import_batches as b', 'b.id', '=', 'e.import_batch_id')->whereNull('b.id')->exists(),
            'conflict events without conflicts' => DB::table('import_conflict_events as e')
                ->leftJoin('import_conflicts as c', 'c.id', '=', 'e.import_conflict_id')->whereNull('c.id')->exists(),
            'governed run events without runs' => DB::table('governed_execution_run_events as e')
                ->leftJoin('governed_execution_runs as r', 'r.id', '=', 'e.run_id')->whereNull('r.id')->exists(),
        ];

        $found = array_keys(array_filter($orphans));
        if ($found !== []) {
            throw new RuntimeException('Audit-retention foreign-key migration found orphan history; reconcile explicitly before continuing: '.implode('; ', $found));
        }

        Schema::table('identity_resolution_decisions', function (Blueprint $table): void {
            $table->dropForeign(['import_row_id']);
            $table->foreign('import_row_id', 'identity_decision_row_restrict_fk')
                ->references('id')->on('import_rows')->restrictOnDelete();
        });
        Schema::table('verification_decisions', function (Blueprint $table): void {
            $table->dropForeign(['assertion_id']);
            $table->foreign('assertion_id', 'verification_decision_assertion_restrict_fk')
                ->references('id')->on('assertions')->restrictOnDelete();
        });
        Schema::table('import_reconciliation_events', function (Blueprint $table): void {
            $table->dropForeign(['import_batch_id']);
            $table->foreign('import_batch_id', 'import_reconciliation_batch_restrict_fk')
                ->references('id')->on('import_batches')->restrictOnDelete();
        });
        Schema::table('import_promotion_decisions', function (Blueprint $table): void {
            $table->dropForeign(['import_row_id']);
            $table->foreign('import_row_id', 'import_promotion_decision_row_restrict_fk')
                ->references('id')->on('import_rows')->restrictOnDelete();
        });
        Schema::table('import_promotion_events', function (Blueprint $table): void {
            $table->dropForeign(['import_row_id']);
            $table->dropForeign(['import_batch_id']);
            $table->foreign('import_row_id', 'import_promotion_event_row_restrict_fk')
                ->references('id')->on('import_rows')->restrictOnDelete();
            $table->foreign('import_batch_id', 'import_promotion_event_batch_restrict_fk')
                ->references('id')->on('import_batches')->restrictOnDelete();
        });
        Schema::table('reverification_events', function (Blueprint $table): void {
            $table->dropForeign(['queue_id']);
            $table->foreign('queue_id', 'reverification_event_queue_restrict_fk')
                ->references('id')->on('data_reverification_queue')->restrictOnDelete();
        });
        Schema::table('structured_enrichment_promotion_events', function (Blueprint $table): void {
            $table->dropForeign('struct_enrichment_row_fk');
            $table->dropForeign(['import_batch_id']);
            $table->foreign('enrichment_import_row_id', 'structured_enrichment_row_restrict_fk')
                ->references('id')->on('enrichment_import_rows')->restrictOnDelete();
            $table->foreign('import_batch_id', 'structured_enrichment_batch_restrict_fk')
                ->references('id')->on('import_batches')->restrictOnDelete();
        });
        Schema::table('import_conflict_events', function (Blueprint $table): void {
            $table->dropForeign(['import_conflict_id']);
            $table->foreign('import_conflict_id', 'import_conflict_event_restrict_fk')
                ->references('id')->on('import_conflicts')->restrictOnDelete();
        });
        Schema::table('governed_execution_run_events', function (Blueprint $table): void {
            $table->dropForeign(['run_id']);
            $table->foreign('run_id', 'governed_run_event_restrict_fk')
                ->references('id')->on('governed_execution_runs')->restrictOnDelete();
        });
        $this->installMysqlGuards();
    }

    private function installMysqlGuards(): void
    {
        foreach (self::MYSQL_APPEND_ONLY_TABLES as $table) {
            $suffix = substr(hash('sha256', $table), 0, 16);
            $updateBody = $table === 'import_reconciliation_events'
                ? "BEGIN
                    IF NOT (
                        OLD.import_row_id IS NOT NULL AND NEW.import_row_id IS NULL
                        AND OLD.id <=> NEW.id
                        AND OLD.import_batch_id <=> NEW.import_batch_id
                        AND OLD.event_type <=> NEW.event_type
                        AND OLD.state <=> NEW.state
                        AND OLD.severity <=> NEW.severity
                        AND OLD.subject_type <=> NEW.subject_type
                        AND OLD.subject_id <=> NEW.subject_id
                        AND OLD.description <=> NEW.description
                        AND OLD.metadata <=> NEW.metadata
                        AND OLD.actor_type <=> NEW.actor_type
                        AND OLD.actor_id <=> NEW.actor_id
                        AND OLD.occurred_at <=> NEW.occurred_at
                        AND OLD.created_at <=> NEW.created_at
                        AND OLD.updated_at <=> NEW.updated_at
                    ) THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Import reconciliation history is immutable except nullable row detachment';
                    END IF;
                END"
                : "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Append-only audit history cannot be changed or deleted'";
            $this->createMysqlTriggerIfMissing(
                'audit_immut_'.$suffix.'_u',
                $table,
                'UPDATE',
                $updateBody
            );
            $this->createMysqlTriggerIfMissing(
                'audit_immut_'.$suffix.'_d',
                $table,
                'DELETE',
                "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Append-only audit history cannot be changed or deleted'"
            );
        }

        $this->createMysqlTriggerIfMissing(
            'identity_decision_update_guard',
            'identity_resolution_decisions',
            'UPDATE',
            "BEGIN
                IF NOT (
                    OLD.decision_identity_key IS NULL AND NEW.decision_identity_key IS NOT NULL
                    AND OLD.id <=> NEW.id
                    AND OLD.import_row_id <=> NEW.import_row_id
                    AND OLD.institution_id <=> NEW.institution_id
                    AND OLD.decision <=> NEW.decision
                    AND OLD.actor_type <=> NEW.actor_type
                    AND OLD.actor_id <=> NEW.actor_id
                    AND OLD.reason <=> NEW.reason
                    AND OLD.decided_at <=> NEW.decided_at
                    AND OLD.created_at <=> NEW.created_at
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identity decision history is immutable except one-time key enrichment';
                END IF;
            END"
        );
        $this->createMysqlTriggerIfMissing(
            'identity_decision_delete_guard',
            'identity_resolution_decisions',
            'DELETE',
            "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identity decision history cannot be deleted'"
        );
    }

    private function createMysqlTriggerIfMissing(string $name, string $table, string $event, string $body): void
    {
        $exists = DB::table('information_schema.TRIGGERS')
            ->where('TRIGGER_SCHEMA', DB::getDatabaseName())
            ->where('TRIGGER_NAME', $name)
            ->exists();

        if (! $exists) {
            DB::unprepared('CREATE TRIGGER '.$name.' BEFORE '.$event.' ON '.$table.' FOR EACH ROW '.$body);
        }
    }

    private function dropMysqlGuards(): void
    {
        foreach (self::MYSQL_APPEND_ONLY_TABLES as $table) {
            $suffix = substr(hash('sha256', $table), 0, 16);
            DB::unprepared('DROP TRIGGER IF EXISTS audit_immut_'.$suffix.'_u');
            DB::unprepared('DROP TRIGGER IF EXISTS audit_immut_'.$suffix.'_d');
        }
        DB::unprepared('DROP TRIGGER IF EXISTS identity_decision_update_guard');
        DB::unprepared('DROP TRIGGER IF EXISTS identity_decision_delete_guard');
    }

    private function installSqliteGuards(): void
    {
        foreach (self::SQLITE_APPEND_ONLY_TABLES as $table) {
            $suffix = substr(hash('sha256', $table), 0, 16);
            foreach (['UPDATE' => 'u', 'DELETE' => 'd'] as $operation => $short) {
                if ($table === 'import_reconciliation_events' && $operation === 'UPDATE') {
                    DB::unprepared(
                        'CREATE TRIGGER IF NOT EXISTS audit_immut_'.$suffix.'_'.$short
                        .' BEFORE UPDATE ON '.$table
                        ." WHEN NOT (
                            OLD.import_row_id IS NOT NULL AND NEW.import_row_id IS NULL
                            AND OLD.id IS NEW.id
                            AND OLD.import_batch_id IS NEW.import_batch_id
                            AND OLD.event_type IS NEW.event_type
                            AND OLD.state IS NEW.state
                            AND OLD.severity IS NEW.severity
                            AND OLD.subject_type IS NEW.subject_type
                            AND OLD.subject_id IS NEW.subject_id
                            AND OLD.description IS NEW.description
                            AND OLD.metadata IS NEW.metadata
                            AND OLD.actor_type IS NEW.actor_type
                            AND OLD.actor_id IS NEW.actor_id
                            AND OLD.occurred_at IS NEW.occurred_at
                            AND OLD.created_at IS NEW.created_at
                            AND OLD.updated_at IS NEW.updated_at
                        )
                        BEGIN SELECT RAISE(ABORT, 'Import reconciliation history is immutable except nullable row detachment'); END"
                    );
                    continue;
                }

                DB::unprepared(
                    'CREATE TRIGGER IF NOT EXISTS audit_immut_'.$suffix.'_'.$short
                    .' BEFORE '.$operation.' ON '.$table
                    ." BEGIN SELECT RAISE(ABORT, 'Append-only audit history cannot be changed or deleted'); END"
                );
            }
        }

        DB::unprepared(
            "CREATE TRIGGER IF NOT EXISTS identity_decision_update_guard BEFORE UPDATE ON identity_resolution_decisions
            WHEN NOT (
                OLD.decision_identity_key IS NULL AND NEW.decision_identity_key IS NOT NULL
                AND OLD.id IS NEW.id
                AND OLD.import_row_id IS NEW.import_row_id
                AND OLD.institution_id IS NEW.institution_id
                AND OLD.decision IS NEW.decision
                AND OLD.actor_type IS NEW.actor_type
                AND OLD.actor_id IS NEW.actor_id
                AND OLD.reason IS NEW.reason
                AND OLD.decided_at IS NEW.decided_at
                AND OLD.created_at IS NEW.created_at
            )
            BEGIN SELECT RAISE(ABORT, 'Identity decision history is immutable except one-time key enrichment'); END"
        );
        DB::unprepared(
            "CREATE TRIGGER IF NOT EXISTS identity_decision_delete_guard BEFORE DELETE ON identity_resolution_decisions
            BEGIN SELECT RAISE(ABORT, 'Identity decision history cannot be deleted'); END"
        );

        foreach (self::SQLITE_PARENT_GUARDS as $table => $conditions) {
            $name = 'audit_parent_guard_'.substr(hash('sha256', $table), 0, 16);
            DB::unprepared(
                'CREATE TRIGGER IF NOT EXISTS '.$name.' BEFORE DELETE ON '.$table
                .' WHEN '.implode(' OR ', $conditions)
                ." BEGIN SELECT RAISE(ABORT, 'Parent deletion would remove retained audit history'); END"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            foreach (self::SQLITE_APPEND_ONLY_TABLES as $table) {
                $suffix = substr(hash('sha256', $table), 0, 16);
                foreach (['u', 'd'] as $short) {
                    DB::unprepared('DROP TRIGGER IF EXISTS audit_immut_'.$suffix.'_'.$short);
                }
            }
            DB::unprepared('DROP TRIGGER IF EXISTS identity_decision_update_guard');
            DB::unprepared('DROP TRIGGER IF EXISTS identity_decision_delete_guard');
            foreach (array_keys(self::SQLITE_PARENT_GUARDS) as $table) {
                DB::unprepared('DROP TRIGGER IF EXISTS audit_parent_guard_'.substr(hash('sha256', $table), 0, 16));
            }
            return;
        }

        $this->dropMysqlGuards();

        Schema::table('governed_execution_run_events', function (Blueprint $table): void {
            $table->dropForeign('governed_run_event_restrict_fk');
            $table->foreign('run_id')->references('id')->on('governed_execution_runs')->cascadeOnDelete();
        });
        Schema::table('import_conflict_events', function (Blueprint $table): void {
            $table->dropForeign('import_conflict_event_restrict_fk');
            $table->foreign('import_conflict_id')->references('id')->on('import_conflicts')->cascadeOnDelete();
        });
        Schema::table('structured_enrichment_promotion_events', function (Blueprint $table): void {
            $table->dropForeign('structured_enrichment_batch_restrict_fk');
            $table->dropForeign('structured_enrichment_row_restrict_fk');
            $table->foreign('enrichment_import_row_id', 'struct_enrichment_row_fk')
                ->references('id')->on('enrichment_import_rows')->cascadeOnDelete();
            $table->foreign('import_batch_id')->references('id')->on('import_batches')->cascadeOnDelete();
        });
        Schema::table('reverification_events', function (Blueprint $table): void {
            $table->dropForeign('reverification_event_queue_restrict_fk');
            $table->foreign('queue_id')->references('id')->on('data_reverification_queue')->cascadeOnDelete();
        });
        Schema::table('import_promotion_events', function (Blueprint $table): void {
            $table->dropForeign('import_promotion_event_batch_restrict_fk');
            $table->dropForeign('import_promotion_event_row_restrict_fk');
            $table->foreign('import_row_id')->references('id')->on('import_rows')->cascadeOnDelete();
            $table->foreign('import_batch_id')->references('id')->on('import_batches')->cascadeOnDelete();
        });
        Schema::table('import_promotion_decisions', function (Blueprint $table): void {
            $table->dropForeign('import_promotion_decision_row_restrict_fk');
            $table->foreign('import_row_id')->references('id')->on('import_rows')->cascadeOnDelete();
        });
        Schema::table('import_reconciliation_events', function (Blueprint $table): void {
            $table->dropForeign('import_reconciliation_batch_restrict_fk');
            $table->foreign('import_batch_id')->references('id')->on('import_batches')->cascadeOnDelete();
        });
        Schema::table('verification_decisions', function (Blueprint $table): void {
            $table->dropForeign('verification_decision_assertion_restrict_fk');
            $table->foreign('assertion_id')->references('id')->on('assertions')->cascadeOnDelete();
        });
        Schema::table('identity_resolution_decisions', function (Blueprint $table): void {
            $table->dropForeign('identity_decision_row_restrict_fk');
            $table->foreign('import_row_id')->references('id')->on('import_rows')->cascadeOnDelete();
        });
    }
};

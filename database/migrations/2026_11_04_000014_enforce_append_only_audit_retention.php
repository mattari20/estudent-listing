<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
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

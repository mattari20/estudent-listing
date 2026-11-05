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
            'monitoring policy runs without a policy' => DB::table('monitoring_policy_runs as r')
                ->leftJoin('source_monitoring_policies as p', 'p.id', '=', 'r.source_monitoring_policy_id')
                ->whereNull('p.id')->exists(),
            'monitoring policy runs without a governed execution run' => DB::table('monitoring_policy_runs as r')
                ->leftJoin('governed_execution_runs as g', 'g.id', '=', 'r.execution_run_id')
                ->whereNotNull('r.execution_run_id')->whereNull('g.id')->exists(),
            'monitoring source checkpoints without a policy run' => DB::table('monitoring_policy_run_sources as s')
                ->leftJoin('monitoring_policy_runs as r', 'r.id', '=', 's.monitoring_policy_run_id')
                ->whereNull('r.id')->exists(),
            'monitoring source checkpoints without a source' => DB::table('monitoring_policy_run_sources as s')
                ->leftJoin('sources as x', 'x.id', '=', 's.source_id')
                ->whereNull('x.id')->exists(),
            'monitoring source checkpoints without a retrieval run' => DB::table('monitoring_policy_run_sources as s')
                ->leftJoin('source_retrieval_runs as r', 'r.id', '=', 's.retrieval_run_id')
                ->whereNotNull('s.retrieval_run_id')->whereNull('r.id')->exists(),
            'monitoring source checkpoints without an observation' => DB::table('monitoring_policy_run_sources as s')
                ->leftJoin('source_change_observations as o', 'o.id', '=', 's.observation_id')
                ->whereNotNull('s.observation_id')->whereNull('o.id')->exists(),
            'monitoring alert identities without an active alert' => DB::table('monitoring_alert_identities as i')
                ->leftJoin('monitoring_alerts as a', 'a.id', '=', 'i.active_alert_id')
                ->whereNotNull('i.active_alert_id')->whereNull('a.id')->exists(),
        ];

        $found = array_keys(array_filter($orphans));
        if ($found !== []) {
            throw new RuntimeException('Monitoring checkpoint foreign-key migration found orphan records; reconcile explicitly before continuing: '.implode('; ', $found));
        }

        Schema::table('monitoring_policy_runs', function (Blueprint $table): void {
            $table->timestamp('heartbeat_at')->nullable()->after('started_at');
            $table->foreign('source_monitoring_policy_id', 'mp_run_policy_fk')
                ->references('id')->on('source_monitoring_policies')->restrictOnDelete();
            $table->foreign('execution_run_id', 'mp_run_exec_fk')
                ->references('id')->on('governed_execution_runs')->nullOnDelete();
        });

        Schema::table('monitoring_policy_run_sources', function (Blueprint $table): void {
            $table->foreign('monitoring_policy_run_id', 'mp_source_run_fk')
                ->references('id')->on('monitoring_policy_runs')->restrictOnDelete();
            $table->foreign('source_id', 'mp_source_source_fk')
                ->references('id')->on('sources')->restrictOnDelete();
            $table->foreign('retrieval_run_id', 'mp_source_retrieval_fk')
                ->references('id')->on('source_retrieval_runs')->nullOnDelete();
            $table->foreign('observation_id', 'mp_source_observation_fk')
                ->references('id')->on('source_change_observations')->nullOnDelete();
        });

        Schema::table('monitoring_alert_identities', function (Blueprint $table): void {
            $table->foreign('active_alert_id', 'mai_active_alert_fk')
                ->references('id')->on('monitoring_alerts')->nullOnDelete();
        });

        DB::table('monitoring_policy_runs')->whereNull('heartbeat_at')->update(['heartbeat_at' => DB::raw('started_at')]);
    }

    public function down(): void
    {
        Schema::table('monitoring_alert_identities', function (Blueprint $table): void {
            $table->dropForeign('mai_active_alert_fk');
        });

        Schema::table('monitoring_policy_run_sources', function (Blueprint $table): void {
            $table->dropForeign('mp_source_observation_fk');
            $table->dropForeign('mp_source_retrieval_fk');
            $table->dropForeign('mp_source_source_fk');
            $table->dropForeign('mp_source_run_fk');
        });

        Schema::table('monitoring_policy_runs', function (Blueprint $table): void {
            $table->dropForeign('mp_run_exec_fk');
            $table->dropForeign('mp_run_policy_fk');
            $table->dropColumn('heartbeat_at');
        });
    }
};

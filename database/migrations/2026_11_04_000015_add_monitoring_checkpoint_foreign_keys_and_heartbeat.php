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

        if (! Schema::hasColumn('monitoring_policy_runs', 'heartbeat_at')) {
            Schema::table('monitoring_policy_runs', function (Blueprint $table): void {
                $table->timestamp('heartbeat_at')->nullable()->after('started_at');
            });
        } elseif (DB::getDriverName() === 'mysql') {
            $heartbeat = DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'monitoring_policy_runs')
                ->where('COLUMN_NAME', 'heartbeat_at')
                ->first();

            if ($heartbeat === null || strtolower((string) $heartbeat->COLUMN_TYPE) !== 'timestamp' || $heartbeat->IS_NULLABLE !== 'YES') {
                throw new RuntimeException('Existing monitoring_policy_runs.heartbeat_at has incompatible metadata; expected nullable TIMESTAMP.');
            }
        }

        if (DB::getDriverName() === 'mysql') {
            foreach ([
                ['monitoring_policy_runs', 'source_monitoring_policy_id', 'mp_run_policy_fk', 'source_monitoring_policies', 'restrict'],
                ['monitoring_policy_runs', 'execution_run_id', 'mp_run_exec_fk', 'governed_execution_runs', 'null'],
                ['monitoring_policy_run_sources', 'monitoring_policy_run_id', 'mp_source_run_fk', 'monitoring_policy_runs', 'restrict'],
                ['monitoring_policy_run_sources', 'source_id', 'mp_source_source_fk', 'sources', 'restrict'],
                ['monitoring_policy_run_sources', 'retrieval_run_id', 'mp_source_retrieval_fk', 'source_retrieval_runs', 'null'],
                ['monitoring_policy_run_sources', 'observation_id', 'mp_source_observation_fk', 'source_change_observations', 'null'],
                ['monitoring_alert_identities', 'active_alert_id', 'mai_active_alert_fk', 'monitoring_alerts', 'null'],
            ] as [$tableName, $columnName, $constraintName, $parentTable, $deleteRule]) {
                $this->ensureMysqlForeignKey($tableName, $columnName, $constraintName, $parentTable, $deleteRule);
            }
        } else {
            Schema::table('monitoring_policy_runs', function (Blueprint $table): void {
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
        }

        DB::table('monitoring_policy_runs')->whereNull('heartbeat_at')->update(['heartbeat_at' => DB::raw('started_at')]);
    }

    private function ensureMysqlForeignKey(
        string $tableName,
        string $columnName,
        string $constraintName,
        string $parentTable,
        string $deleteRule,
        string $parentColumn = 'id'
    ): void {
        $existing = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->join('information_schema.REFERENTIAL_CONSTRAINTS', function ($join): void {
                $join->on('REFERENTIAL_CONSTRAINTS.CONSTRAINT_SCHEMA', '=', 'KEY_COLUMN_USAGE.CONSTRAINT_SCHEMA')
                    ->on('REFERENTIAL_CONSTRAINTS.CONSTRAINT_NAME', '=', 'KEY_COLUMN_USAGE.CONSTRAINT_NAME')
                    ->on('REFERENTIAL_CONSTRAINTS.TABLE_NAME', '=', 'KEY_COLUMN_USAGE.TABLE_NAME');
            })
            ->select([
                'KEY_COLUMN_USAGE.CONSTRAINT_NAME',
                'KEY_COLUMN_USAGE.REFERENCED_TABLE_NAME',
                'KEY_COLUMN_USAGE.REFERENCED_COLUMN_NAME',
                'REFERENTIAL_CONSTRAINTS.DELETE_RULE',
                'REFERENTIAL_CONSTRAINTS.UPDATE_RULE',
            ])
            ->where('KEY_COLUMN_USAGE.CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('KEY_COLUMN_USAGE.TABLE_NAME', $tableName)
            ->where('KEY_COLUMN_USAGE.COLUMN_NAME', $columnName)
            ->whereNotNull('KEY_COLUMN_USAGE.REFERENCED_TABLE_NAME')
            ->get();

        foreach ($existing as $foreignKey) {
            if (
                $foreignKey->REFERENCED_TABLE_NAME !== $parentTable
                || $foreignKey->REFERENCED_COLUMN_NAME !== $parentColumn
            ) {
                throw new RuntimeException('Existing foreign key on '.$tableName.'.'.$columnName.' references an unexpected parent; refusing automatic replacement.');
            }

            if ($foreignKey->CONSTRAINT_NAME === $constraintName) {
                $actualRule = strtoupper((string) $foreignKey->DELETE_RULE);
                $expectedRule = $deleteRule === 'null' ? 'SET NULL' : 'RESTRICT';

                if (
                    ! \App\Support\Database\MigrationSchemaGuard::mysqlReferentialActionMatches($actualRule, $expectedRule)
                    || ! \App\Support\Database\MigrationSchemaGuard::mysqlReferentialActionMatches((string) $foreignKey->UPDATE_RULE, 'RESTRICT')
                ) {
                    throw new RuntimeException(
                        'Existing foreign key '.$constraintName.' has incompatible actions (delete='.$actualRule.', update='.(string) $foreignKey->UPDATE_RULE.').'
                    );
                }

                return;
            }

            DB::statement('ALTER TABLE `'.$tableName.'` DROP FOREIGN KEY `'.$foreignKey->CONSTRAINT_NAME.'`');
        }

        Schema::table($tableName, function (Blueprint $blueprint) use ($columnName, $constraintName, $parentTable, $parentColumn, $deleteRule): void {
            $foreign = $blueprint->foreign($columnName, $constraintName)
                ->references($parentColumn)
                ->on($parentTable);

            if ($deleteRule === 'null') {
                $foreign->nullOnDelete();
            } else {
                $foreign->restrictOnDelete();
            }
        });
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

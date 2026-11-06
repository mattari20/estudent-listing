<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('source_change_observations')
            ->select('current_snapshot_id')
            ->whereNotNull('current_snapshot_id')
            ->groupBy('current_snapshot_id')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();

        if ($duplicates) {
            throw new RuntimeException(
                'Cannot add unique source observation identity: duplicate non-NULL current_snapshot_id rows require reviewed reconciliation.'
            );
        }

        if (! Schema::hasColumn('source_snapshots', 'capture_key')) {
            Schema::table('source_snapshots', function (Blueprint $table): void {
                $table->string('capture_key', 64)->nullable();
            });
        } else {
            $column = DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'source_snapshots')
                ->where('COLUMN_NAME', 'capture_key')
                ->first();

            if ($column === null || strtolower((string) $column->COLUMN_TYPE) !== 'varchar(64)' || $column->IS_NULLABLE !== 'YES') {
                throw new RuntimeException(
                    'Existing source_snapshots.capture_key has an incompatible definition; expected nullable VARCHAR(64).'
                );
            }
        }

        $duplicateCaptureKeys = DB::table('source_snapshots')
            ->select('capture_key')
            ->whereNotNull('capture_key')
            ->groupBy('capture_key')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();

        if ($duplicateCaptureKeys) {
            throw new RuntimeException(
                'Duplicate non-NULL source snapshot capture keys exist; reconcile explicitly before adding the unique index.'
            );
        }

        $captureIndex = DB::table('information_schema.STATISTICS')
            ->selectRaw('NON_UNIQUE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS indexed_columns')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'source_snapshots')
            ->where('INDEX_NAME', 'source_snapshots_capture_key_unique')
            ->groupBy('INDEX_NAME')
            ->first();

        if ($captureIndex !== null) {
            if ((int) $captureIndex->NON_UNIQUE !== 0 || $captureIndex->indexed_columns !== 'capture_key') {
                throw new RuntimeException('Existing source snapshot capture-key index has an incompatible definition.');
            }
        } else {
            Schema::table('source_snapshots', function (Blueprint $table): void {
                $table->unique('capture_key', 'source_snapshots_capture_key_unique');
            });
        }

        $observationIndex = DB::table('information_schema.STATISTICS')
            ->selectRaw('NON_UNIQUE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS indexed_columns')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'source_change_observations')
            ->where('INDEX_NAME', 'source_observations_current_snapshot_unique')
            ->groupBy('INDEX_NAME')
            ->first();

        if ($observationIndex !== null) {
            if ((int) $observationIndex->NON_UNIQUE !== 0 || $observationIndex->indexed_columns !== 'current_snapshot_id') {
                throw new RuntimeException('Existing source observation snapshot index has an incompatible definition.');
            }
        } else {
            $existingUnique = DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'source_change_observations')
                ->where('NON_UNIQUE', 0)
                ->where('COLUMN_NAME', 'current_snapshot_id')
                ->where('SEQ_IN_INDEX', 1)
                ->exists();

            if ($existingUnique) {
                throw new RuntimeException(
                    'A unique index already protects source_change_observations.current_snapshot_id under a different name; reconcile the migration naming contract before continuing.'
                );
            }

            Schema::table('source_change_observations', function (Blueprint $table): void {
                $table->unique('current_snapshot_id', 'source_observations_current_snapshot_unique');
            });
        }
    }

    public function down(): void
    {
        $observationIndex = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'source_change_observations')
            ->where('INDEX_NAME', 'source_observations_current_snapshot_unique')
            ->exists();

        if ($observationIndex) {
            Schema::table('source_change_observations', function (Blueprint $table): void {
                $table->dropUnique('source_observations_current_snapshot_unique');
            });
        }

        if (Schema::hasColumn('source_snapshots', 'capture_key')) {
            $captureIndex = DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'source_snapshots')
                ->where('INDEX_NAME', 'source_snapshots_capture_key_unique')
                ->exists();

            if ($captureIndex) {
                Schema::table('source_snapshots', function (Blueprint $table): void {
                    $table->dropUnique('source_snapshots_capture_key_unique');
                });
            }

            Schema::table('source_snapshots', function (Blueprint $table): void {
                $table->dropColumn('capture_key');
            });
        }
    }
};

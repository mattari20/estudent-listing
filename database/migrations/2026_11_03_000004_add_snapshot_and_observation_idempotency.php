<?php

use App\\Support\\Database\\MigrationSchemaGuard;
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

        MigrationSchemaGuard::ensureNullableUniqueString(
            'source_snapshots',
            'capture_key',
            64,
            'source_snapshots_capture_key_unique'
        );

        $observationIndex = DB::getDriverName() === 'mysql'
            ? DB::table('information_schema.STATISTICS')
                ->selectRaw('MIN(NON_UNIQUE) AS NON_UNIQUE, COUNT(*) AS COLUMN_COUNT, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS indexed_columns, MAX(SUB_PART) AS PREFIX_LENGTH, MIN(INDEX_TYPE) AS INDEX_TYPE')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'source_change_observations')
                ->where('INDEX_NAME', 'source_observations_current_snapshot_unique')
                ->groupBy('INDEX_NAME')
                ->first()
            : (Schema::hasIndex('source_change_observations', 'source_observations_current_snapshot_unique') ? (object) ['NON_UNIQUE' => 0, 'indexed_columns' => 'current_snapshot_id'] : null);

        if ($observationIndex !== null) {
            if ((int) $observationIndex->NON_UNIQUE !== 0 || (int) $observationIndex->COLUMN_COUNT !== 1 || $observationIndex->indexed_columns !== 'current_snapshot_id' || $observationIndex->PREFIX_LENGTH !== null || strtoupper((string) $observationIndex->INDEX_TYPE) !== 'BTREE') {
                throw new RuntimeException('Existing source observation snapshot index has an incompatible definition.');
            }
        } else {
            $existingUnique = DB::getDriverName() === 'mysql'
                ? DB::table('information_schema.STATISTICS')
                    ->select('INDEX_NAME')
                    ->where('TABLE_SCHEMA', DB::getDatabaseName())
                    ->where('TABLE_NAME', 'source_change_observations')
                    ->where('NON_UNIQUE', 0)
                    ->groupBy('INDEX_NAME')
                    ->havingRaw('COUNT(*) = 1')
                    ->havingRaw(
                        'SUM(CASE WHEN COLUMN_NAME = ? AND SEQ_IN_INDEX = 1 AND SUB_PART IS NULL AND INDEX_TYPE = ? THEN 1 ELSE 0 END) = 1',
                        ['current_snapshot_id', 'BTREE']
                    )
                    ->exists()
                : Schema::hasIndex('source_change_observations', ['current_snapshot_id'], 'unique');

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
        $observationIndex = DB::getDriverName() === 'mysql'
            ? DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'source_change_observations')
                ->where('INDEX_NAME', 'source_observations_current_snapshot_unique')
                ->exists()
            : Schema::hasIndex('source_change_observations', 'source_observations_current_snapshot_unique');

        if ($observationIndex) {
            Schema::table('source_change_observations', function (Blueprint $table): void {
                $table->dropUnique('source_observations_current_snapshot_unique');
            });
        }

        if (Schema::hasColumn('source_snapshots', 'capture_key')) {
            $captureIndex = DB::getDriverName() === 'mysql'
                ? DB::table('information_schema.STATISTICS')
                    ->where('TABLE_SCHEMA', DB::getDatabaseName())
                    ->where('TABLE_NAME', 'source_snapshots')
                    ->where('INDEX_NAME', 'source_snapshots_capture_key_unique')
                    ->exists()
                : Schema::hasIndex('source_snapshots', 'source_snapshots_capture_key_unique');

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

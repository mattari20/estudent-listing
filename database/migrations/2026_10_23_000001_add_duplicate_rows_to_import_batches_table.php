<?php

use App\Support\Database\MigrationSchemaGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('import_batches', 'duplicate_rows')) {
            Schema::table('import_batches', function (Blueprint $table): void {
                $table->unsignedInteger('duplicate_rows')->default(0)->after('candidate_rows');
            });

            return;
        }

        if (DB::getDriverName() === 'mysql') {
            $column = DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'import_batches')
                ->where('COLUMN_NAME', 'duplicate_rows')
                ->first();

            if (
                $column === null
                || ! MigrationSchemaGuard::mysqlColumnTypeMatches((string) $column->COLUMN_TYPE, 'int unsigned')
                || $column->IS_NULLABLE !== 'NO'
                || (string) $column->COLUMN_DEFAULT !== '0'
            ) {
                throw new RuntimeException(
                    'Existing import_batches.duplicate_rows has incompatible metadata; expected NOT NULL INT UNSIGNED DEFAULT 0.'
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('import_batches', 'duplicate_rows')) {
            Schema::table('import_batches', function (Blueprint $table): void {
                $table->dropColumn('duplicate_rows');
            });
        }
    }
};

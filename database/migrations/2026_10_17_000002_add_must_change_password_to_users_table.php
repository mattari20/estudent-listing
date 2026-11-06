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
        if (! Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('must_change_password')->default(false);
            });

            return;
        }

        if (DB::getDriverName() === 'mysql') {
            $column = DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'users')
                ->where('COLUMN_NAME', 'must_change_password')
                ->first();

            if (
                $column === null
                || ! MigrationSchemaGuard::mysqlColumnTypeMatches((string) $column->COLUMN_TYPE, 'tinyint(1)')
                || $column->IS_NULLABLE !== 'NO'
                || (string) $column->COLUMN_DEFAULT !== '0'
            ) {
                throw new RuntimeException(
                    'Existing users.must_change_password has incompatible metadata; expected NOT NULL TINYINT(1) DEFAULT 0.'
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('must_change_password');
            });
        }
    }
};

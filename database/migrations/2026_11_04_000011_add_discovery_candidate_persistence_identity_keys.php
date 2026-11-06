<?php

use App\Support\Database\MigrationSchemaGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        MigrationSchemaGuard::ensureNullableUniqueString(
            'import_batches',
            'discovery_identity_key',
            64,
            'import_batches_discovery_identity_unique'
        );
        MigrationSchemaGuard::ensureNullableUniqueString(
            'evidence',
            'discovery_identity_key',
            64,
            'evidence_discovery_identity_unique'
        );
        MigrationSchemaGuard::ensureNullableUniqueString(
            'import_rows',
            'discovery_identity_key',
            64,
            'import_rows_discovery_identity_unique'
        );
        MigrationSchemaGuard::ensureNullableUniqueString(
            'assertions',
            'discovery_identity_key',
            64,
            'assertions_discovery_identity_unique'
        );
    }

    public function down(): void
    {
        foreach ([
            'assertions',
            'import_rows',
            'evidence',
            'import_batches',
        ] as $tableName) {
            if (! Schema::hasColumn($tableName, 'discovery_identity_key')) {
                continue;
            }

            $indexName = $tableName.'_discovery_identity_unique';
            if (Schema::hasIndex($tableName, $indexName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                    $table->dropUnique($indexName);
                });
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('discovery_identity_key');
            });
        }
    }
};

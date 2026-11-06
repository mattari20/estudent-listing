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
            'institute_discovery_run_outcomes',
            'idempotency_key',
            64,
            'institute_discovery_run_outcomes_idempotency_key_unique'
        );
    }

    public function down(): void
    {
        if (Schema::hasColumn('institute_discovery_run_outcomes', 'idempotency_key')) {
            Schema::table('institute_discovery_run_outcomes', function (Blueprint $table): void {
                $table->dropUnique('institute_discovery_run_outcomes_idempotency_key_unique');
                $table->dropColumn('idempotency_key');
            });
        }
    }
};

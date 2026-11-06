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
            'source_retrieval_runs',
            'idempotency_key',
            64,
            'source_retrieval_runs_idempotency_key_unique'
        );
    }

    public function down(): void
    {
        if (Schema::hasColumn('source_retrieval_runs', 'idempotency_key')) {
            Schema::table('source_retrieval_runs', function (Blueprint $table): void {
                $table->dropUnique('source_retrieval_runs_idempotency_key_unique');
                $table->dropColumn('idempotency_key');
            });
        }
    }
};

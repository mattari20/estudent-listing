<?php

use App\Support\Database\MigrationSchemaGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        MigrationSchemaGuard::ensureNullableUniqueChar(
            'identity_match_candidates',
            'identity_key',
            64,
            'identity_match_candidates_identity_key_unique'
        );
        MigrationSchemaGuard::ensureNullableUniqueChar(
            'identity_resolution_decisions',
            'decision_identity_key',
            64,
            'identity_resolution_decisions_identity_key_unique'
        );
        MigrationSchemaGuard::ensureNullableUniqueChar(
            'import_conflicts',
            'identity_key',
            64,
            'import_conflicts_identity_key_unique'
        );

        MigrationSchemaGuard::createTableIfMissing('import_conflict_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_conflict_id')->constrained('import_conflicts')->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->string('status_before', 30)->nullable();
            $table->string('status_after', 30);
            $table->string('actor_type', 40)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->char('idempotency_key', 64)->unique();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['import_conflict_id', 'occurred_at']);
        }, [
            'id', 'import_conflict_id', 'event_type', 'status_before', 'status_after',
            'actor_type', 'actor_id', 'reason', 'metadata', 'idempotency_key',
            'occurred_at', 'created_at', 'updated_at',
        ], [
            'id' => ['type' => 'bigint unsigned', 'nullable' => false, 'auto_increment' => true],
            'import_conflict_id' => ['type' => 'bigint unsigned', 'nullable' => false],
            'event_type' => ['type' => 'varchar(40)', 'nullable' => false],
            'status_before' => ['type' => 'varchar(30)', 'nullable' => true, 'default' => null],
            'status_after' => ['type' => 'varchar(30)', 'nullable' => false],
            'actor_type' => ['type' => 'varchar(40)', 'nullable' => true, 'default' => null],
            'actor_id' => ['type' => 'bigint unsigned', 'nullable' => true, 'default' => null],
            'reason' => ['type' => 'text', 'nullable' => true, 'default' => null],
            'metadata' => ['type' => ['json', 'longtext'], 'nullable' => true, 'default' => null],
            'idempotency_key' => ['type' => 'char(64)', 'nullable' => false],
            'occurred_at' => ['type' => 'timestamp', 'nullable' => false],
            'created_at' => ['type' => 'timestamp', 'nullable' => true, 'default' => null],
            'updated_at' => ['type' => 'timestamp', 'nullable' => true, 'default' => null],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('import_conflict_events');

        foreach ([
            ['identity_resolution_decisions', 'decision_identity_key', 'identity_resolution_decisions_identity_key_unique'],
            ['import_conflicts', 'identity_key', 'import_conflicts_identity_key_unique'],
            ['identity_match_candidates', 'identity_key', 'identity_match_candidates_identity_key_unique'],
        ] as [$tableName, $columnName, $indexName]) {
            if (! Schema::hasColumn($tableName, $columnName)) {
                continue;
            }

            if (Schema::hasIndex($tableName, $indexName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                    $table->dropUnique($indexName);
                });
            }

            Schema::table($tableName, function (Blueprint $table) use ($columnName): void {
                $table->dropColumn($columnName);
            });
        }
    }
};

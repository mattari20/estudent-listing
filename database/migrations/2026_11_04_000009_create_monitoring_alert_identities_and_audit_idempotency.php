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
        $duplicates = DB::table('monitoring_alerts')->select('dedupe_key')
            ->selectRaw('COUNT(*) AS aggregate')->where('status', 'open')
            ->groupBy('dedupe_key')->havingRaw('COUNT(*) > 1')->limit(1)->first();

        if ($duplicates !== null) {
            throw new RuntimeException('Duplicate open monitoring alerts exist; reconcile explicitly before adding active identities.');
        }

        if (! Schema::hasTable('monitoring_alert_identities')) {
            Schema::create('monitoring_alert_identities', function (Blueprint $table): void {
                $table->string('dedupe_key', 40)->primary();
                $table->unsignedBigInteger('active_alert_id')->nullable()->index();
                $table->timestamps();
            });
        } else {
            $requiredColumns = ['dedupe_key', 'active_alert_id', 'created_at', 'updated_at'];
            $missingColumns = array_values(array_filter(
                $requiredColumns,
                static fn (string $column): bool => ! Schema::hasColumn('monitoring_alert_identities', $column)
            ));

            if ($missingColumns !== []) {
                throw new RuntimeException(
                    'Existing monitoring_alert_identities table is partially applied; missing columns: '.implode(', ', $missingColumns).'. Reconcile the exact schema before retrying.'
                );
            }

            if (DB::getDriverName() === 'mysql') {
                MigrationSchemaGuard::assertExistingColumnDefinitions('monitoring_alert_identities', [
                    'dedupe_key' => ['type' => 'varchar(40)', 'nullable' => false],
                    'active_alert_id' => ['type' => 'bigint unsigned', 'nullable' => true, 'default' => null],
                    'created_at' => ['type' => 'timestamp', 'nullable' => true, 'default' => null],
                    'updated_at' => ['type' => 'timestamp', 'nullable' => true, 'default' => null],
                ]);
            }

            $primaryKey = DB::getDriverName() === 'mysql'
                ? DB::table('information_schema.STATISTICS')
                    ->where('TABLE_SCHEMA', DB::getDatabaseName())
                    ->where('TABLE_NAME', 'monitoring_alert_identities')
                    ->where('INDEX_NAME', 'PRIMARY')
                    ->orderBy('SEQ_IN_INDEX')
                    ->pluck('COLUMN_NAME')
                    ->all()
                : ['dedupe_key'];

            if ($primaryKey !== ['dedupe_key']) {
                throw new RuntimeException(
                    'Existing monitoring_alert_identities table has an incompatible primary key; expected PRIMARY(dedupe_key).'
                );
            }
        }

        DB::table('monitoring_alerts')->where('status', 'open')->orderBy('id')->chunkById(500, function ($alerts): void {
            foreach ($alerts as $alert) {
                $existingByKey = DB::table('monitoring_alert_identities')
                    ->where('dedupe_key', $alert->dedupe_key)
                    ->first();

                if ($existingByKey !== null) {
                    if ((int) $existingByKey->active_alert_id !== (int) $alert->id) {
                        throw new RuntimeException(
                            'Existing monitoring alert identity '.$alert->dedupe_key.' points to a different alert; no automatic remapping is permitted.'
                        );
                    }

                    continue;
                }

                $existingByAlert = DB::table('monitoring_alert_identities')
                    ->where('active_alert_id', $alert->id)
                    ->first();

                if ($existingByAlert !== null) {
                    throw new RuntimeException(
                        'Existing monitoring alert identity for alert '.$alert->id.' has a different dedupe key; reconcile explicitly before retrying.'
                    );
                }

                DB::table('monitoring_alert_identities')->insert([
                    'dedupe_key' => $alert->dedupe_key,
                    'active_alert_id' => $alert->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        MigrationSchemaGuard::ensureNullableUniqueString(
            'security_audit_events',
            'monitoring_idempotency_key',
            191,
            'security_audit_events_monitoring_idempotency_key_unique'
        );
    }

    public function down(): void
    {
        if (Schema::hasColumn('security_audit_events', 'monitoring_idempotency_key')) {
            $index = DB::getDriverName() === 'mysql'
                ? DB::table('information_schema.STATISTICS')
                    ->where('TABLE_SCHEMA', DB::getDatabaseName())
                    ->where('TABLE_NAME', 'security_audit_events')
                    ->where('INDEX_NAME', 'security_audit_events_monitoring_idempotency_key_unique')
                    ->exists()
                : Schema::hasIndex('security_audit_events', 'security_audit_events_monitoring_idempotency_key_unique');

            if ($index) {
                Schema::table('security_audit_events', function (Blueprint $table): void {
                    $table->dropUnique('security_audit_events_monitoring_idempotency_key_unique');
                });
            }

            Schema::table('security_audit_events', function (Blueprint $table): void {
                $table->dropColumn('monitoring_idempotency_key');
            });
        }

        Schema::dropIfExists('monitoring_alert_identities');
    }
};

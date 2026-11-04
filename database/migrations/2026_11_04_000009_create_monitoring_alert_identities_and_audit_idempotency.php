<?php

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

        Schema::create('monitoring_alert_identities', function (Blueprint $table): void {
            $table->string('dedupe_key', 40)->primary();
            $table->unsignedBigInteger('active_alert_id')->nullable()->index();
            $table->timestamps();
        });

        DB::table('monitoring_alerts')->where('status', 'open')->orderBy('id')->chunkById(500, function ($alerts): void {
            foreach ($alerts as $alert) {
                DB::table('monitoring_alert_identities')->insert([
                    'dedupe_key' => $alert->dedupe_key,
                    'active_alert_id' => $alert->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        Schema::table('security_audit_events', function (Blueprint $table): void {
            $table->string('monitoring_idempotency_key', 191)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('security_audit_events', function (Blueprint $table): void {
            $table->dropUnique(['monitoring_idempotency_key']);
            $table->dropColumn('monitoring_idempotency_key');
        });
        Schema::dropIfExists('monitoring_alert_identities');
    }
};

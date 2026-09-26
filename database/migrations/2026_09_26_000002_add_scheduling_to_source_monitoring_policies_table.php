<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('source_monitoring_policies', function (Blueprint $table): void {
            $table->string('cadence')->default('daily')->after('last_run_at');
            $table->timestamp('next_run_at')->nullable()->after('cadence');
            $table->timestamp('last_success_at')->nullable()->after('next_run_at');
            $table->timestamp('last_failure_at')->nullable()->after('last_success_at');
            $table->unsignedInteger('consecutive_failures')->default(0)->after('last_failure_at');
        });
    }

    public function down(): void
    {
        Schema::table('source_monitoring_policies', function (Blueprint $table): void {
            $table->dropColumn([
                'cadence',
                'next_run_at',
                'last_success_at',
                'last_failure_at',
                'consecutive_failures',
            ]);
        });
    }
};

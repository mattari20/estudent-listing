<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'cadence' => fn (Blueprint $table) => $table->string('cadence')->default('daily'),
            'next_run_at' => fn (Blueprint $table) => $table->timestamp('next_run_at')->nullable(),
            'last_success_at' => fn (Blueprint $table) => $table->timestamp('last_success_at')->nullable(),
            'last_failure_at' => fn (Blueprint $table) => $table->timestamp('last_failure_at')->nullable(),
            'consecutive_failures' => fn (Blueprint $table) => $table->unsignedInteger('consecutive_failures')->default(0),
        ];

        foreach ($columns as $name => $definition) {
            if (Schema::hasColumn('source_monitoring_policies', $name)) {
                continue;
            }

            Schema::table('source_monitoring_policies', function (Blueprint $table) use ($definition): void {
                $definition($table);
            });
        }
    }

    public function down(): void
    {
        $columns = [
            'cadence',
            'next_run_at',
            'last_success_at',
            'last_failure_at',
            'consecutive_failures',
        ];

        foreach ($columns as $name) {
            if (! Schema::hasColumn('source_monitoring_policies', $name)) {
                continue;
            }

            Schema::table('source_monitoring_policies', function (Blueprint $table) use ($name): void {
                $table->dropColumn($name);
            });
        }
    }
};

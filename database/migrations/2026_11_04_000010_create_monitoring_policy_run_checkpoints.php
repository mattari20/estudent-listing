<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_policy_runs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('source_monitoring_policy_id')->index();
            $table->uuid('execution_run_id')->nullable()->index();
            $table->string('status', 20)->index();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->json('summary')->nullable();
            $table->string('failure_code', 160)->nullable();
            $table->timestamps();
        });

        Schema::create('monitoring_policy_run_sources', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('monitoring_policy_run_id');
            $table->unsignedBigInteger('source_id');
            $table->string('status', 20);
            $table->unsignedBigInteger('retrieval_run_id')->nullable();
            $table->unsignedBigInteger('observation_id')->nullable();
            $table->string('failure_code', 160)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->unique(['monitoring_policy_run_id', 'source_id'], 'monitoring_policy_run_source_unique');
            $table->index(['source_id', 'status'], 'monitoring_policy_source_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_policy_run_sources');
        Schema::dropIfExists('monitoring_policy_runs');
    }
};

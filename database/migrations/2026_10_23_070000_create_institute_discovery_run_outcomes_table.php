<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institute_discovery_run_outcomes', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('source_id')->nullable();
            $table->foreign('source_id', 'idrun_outcomes_source_fk')
                ->references('id')->on('sources')->nullOnDelete();

            $table->foreignId('source_change_observation_id')->nullable();
            $table->foreign('source_change_observation_id', 'idrun_outcomes_observation_fk')
                ->references('id')->on('source_change_observations')->nullOnDelete();

            $table->foreignId('snapshot_id')->nullable();
            $table->foreign('snapshot_id', 'idrun_outcomes_snapshot_fk')
                ->references('id')->on('source_snapshots')->nullOnDelete();

            $table->string('profile_key')->nullable();
            $table->string('outcome', 40);
            $table->unsignedInteger('candidate_count')->default(0);
            $table->string('review_package_status', 40)->nullable();
            $table->string('failure_class')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['source_id', 'occurred_at'], 'idrun_outcomes_source_time_idx');
            $table->index(['outcome', 'occurred_at'], 'idrun_outcomes_outcome_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institute_discovery_run_outcomes');
    }
};

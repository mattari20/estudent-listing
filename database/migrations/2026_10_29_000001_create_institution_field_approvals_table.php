<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_field_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->string('field_name', 100);
            $table->longText('expected_current_value')->nullable();
            $table->longText('proposed_value');
            $table->foreignId('source_id')->constrained('sources')->restrictOnDelete();
            $table->foreignId('source_change_observation_id')->constrained('source_change_observations')->restrictOnDelete();
            $table->foreignId('source_snapshot_id')->constrained('source_snapshots')->restrictOnDelete();
            $table->foreignId('evidence_id')->constrained('evidence')->restrictOnDelete();
            $table->text('evidence_url');
            $table->longText('evidence_excerpt');
            $table->string('content_fingerprint', 128);
            $table->timestamp('source_observed_at');
            $table->timestamp('evidence_observed_at')->nullable();
            $table->char('proposal_hash', 64)->unique();
            $table->string('status', 24)->default('pending');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('decision_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->string('application_outcome', 40)->nullable();
            $table->foreignId('applied_refresh_event_id')->nullable()->constrained('official_source_refresh_events')->restrictOnDelete();
            $table->timestamps();

            $table->index(['institution_id', 'field_name', 'status'], 'inst_field_approval_state_idx');
            $table->index(['source_id', 'source_change_observation_id'], 'inst_field_approval_source_idx');
            $table->index(['status', 'decision_at'], 'inst_field_approval_decision_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_field_approvals');
    }
};

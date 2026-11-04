<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_snapshot_reconciliation_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('triggering_snapshot_id')->constrained('source_snapshots')->cascadeOnDelete();
            $table->foreignId('current_snapshot_id')->constrained('source_snapshots')->cascadeOnDelete();
            $table->foreignId('expected_previous_snapshot_id')->nullable()->constrained('source_snapshots')->nullOnDelete();
            $table->foreignId('recorded_previous_snapshot_id')->nullable()->constrained('source_snapshots')->nullOnDelete();
            $table->string('identity_key', 64)->unique();
            $table->string('status', 32)->default('pending_review');
            $table->json('details')->nullable();
            $table->timestamps();
            $table->index(['source_id', 'status'], 'snapshot_reconciliation_source_status_idx');
            $table->index(['current_snapshot_id', 'status'], 'snapshot_reconciliation_current_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_snapshot_reconciliation_requests');
    }
};

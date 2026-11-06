<?php

use App\Support\Database\MigrationSchemaGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        MigrationSchemaGuard::createTableIfMissing('source_snapshot_reconciliation_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('triggering_snapshot_id');
            $table->unsignedBigInteger('current_snapshot_id');
            $table->unsignedBigInteger('expected_previous_snapshot_id')->nullable();
            $table->unsignedBigInteger('recorded_previous_snapshot_id')->nullable();
            $table->string('identity_key', 64)->unique();
            $table->string('status', 32)->default('pending_review');
            $table->json('details')->nullable();
            $table->timestamps();

            $table->foreign('source_id', 'ssr_source_fk')
                ->references('id')->on('sources')->cascadeOnDelete();
            $table->foreign('triggering_snapshot_id', 'ssr_triggering_snapshot_fk')
                ->references('id')->on('source_snapshots')->cascadeOnDelete();
            $table->foreign('current_snapshot_id', 'ssr_current_snapshot_fk')
                ->references('id')->on('source_snapshots')->cascadeOnDelete();
            $table->foreign('expected_previous_snapshot_id', 'ssr_expected_previous_fk')
                ->references('id')->on('source_snapshots')->nullOnDelete();
            $table->foreign('recorded_previous_snapshot_id', 'ssr_recorded_previous_fk')
                ->references('id')->on('source_snapshots')->nullOnDelete();

            $table->index(['source_id', 'status'], 'snapshot_reconciliation_source_status_idx');
            $table->index(['current_snapshot_id', 'status'], 'snapshot_reconciliation_current_status_idx');
        }, [
            'id', 'source_id', 'triggering_snapshot_id', 'current_snapshot_id',
            'expected_previous_snapshot_id', 'recorded_previous_snapshot_id',
            'identity_key', 'status', 'details', 'created_at', 'updated_at',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('source_snapshot_reconciliation_requests');
    }
};

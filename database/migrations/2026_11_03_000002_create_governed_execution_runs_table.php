<?php

use App\Support\Database\MigrationSchemaGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        MigrationSchemaGuard::createTableIfMissing('governed_execution_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('task_key', 120)->index();
            $table->uuid('owner_token')->index();
            $table->string('runner', 80);
            $table->string('application_revision', 40)->nullable();
            $table->string('status', 20)->index();
            $table->timestamp('started_at');
            $table->timestamp('heartbeat_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('failure_code', 160)->nullable();
            $table->timestamps();
        }, ['id', 'task_key', 'owner_token', 'runner', 'application_revision', 'status', 'started_at', 'heartbeat_at', 'finished_at', 'failure_code', 'created_at', 'updated_at']);

        MigrationSchemaGuard::createTableIfMissing('governed_execution_run_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('run_id')->index();
            $table->string('event_type', 30);
            $table->timestamp('occurred_at');
            $table->string('failure_code', 160)->nullable();
            $table->timestamps();
            $table->foreign('run_id')->references('id')->on('governed_execution_runs')->cascadeOnDelete();
        }, ['id', 'run_id', 'event_type', 'occurred_at', 'failure_code', 'created_at', 'updated_at']);
    }

    public function down(): void
    {
        Schema::dropIfExists('governed_execution_run_events');
        Schema::dropIfExists('governed_execution_runs');
    }
};

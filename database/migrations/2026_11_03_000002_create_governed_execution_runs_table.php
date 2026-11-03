<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governed_execution_runs', function (Blueprint $table): void {
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governed_execution_runs');
    }
};

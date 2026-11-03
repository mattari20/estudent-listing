<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governed_execution_leases', function (Blueprint $table): void {
            $table->id();
            $table->string('task_key', 120)->unique();
            $table->uuid('owner_token');
            $table->uuid('run_id');
            $table->timestamp('acquired_at');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governed_execution_leases');
    }
};

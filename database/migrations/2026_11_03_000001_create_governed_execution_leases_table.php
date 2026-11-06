<?php

use App\Support\Database\MigrationSchemaGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        MigrationSchemaGuard::createTableIfMissing('governed_execution_leases', function (Blueprint $table): void {
            $table->id();
            $table->string('task_key', 120)->unique();
            $table->uuid('owner_token');
            $table->uuid('run_id');
            $table->timestamp('acquired_at');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        }, ['id', 'task_key', 'owner_token', 'run_id', 'acquired_at', 'expires_at', 'created_at', 'updated_at'], [
            'id' => ['type' => 'bigint unsigned', 'nullable' => false, 'auto_increment' => true],
            'task_key' => ['type' => 'varchar(120)', 'nullable' => false],
            'owner_token' => ['type' => 'char(36)', 'nullable' => false],
            'run_id' => ['type' => 'char(36)', 'nullable' => false],
            'acquired_at' => ['type' => 'timestamp', 'nullable' => false],
            'expires_at' => ['type' => 'timestamp', 'nullable' => false],
            'created_at' => ['type' => 'timestamp', 'nullable' => true, 'default' => null],
            'updated_at' => ['type' => 'timestamp', 'nullable' => true, 'default' => null],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('governed_execution_leases');
    }
};

<?php

use App\Support\Database\MigrationSchemaGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        MigrationSchemaGuard::createTableIfMissing('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        }, ['email', 'token', 'created_at']);
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};

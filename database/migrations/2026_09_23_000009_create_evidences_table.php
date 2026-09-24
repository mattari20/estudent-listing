<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evidence', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('content_hash')->nullable();
            $table->timestamp('observed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->index(['source_id', 'status']);
            $table->index('observed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence');
    }
};

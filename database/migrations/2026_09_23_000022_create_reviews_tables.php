<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();

            $table->string('reviewer_type', 40)->nullable();
            $table->string('target_type', 40)->default('institution');
            $table->string('title', 200)->nullable();
            $table->text('body');
            $table->unsignedTinyInteger('overall_rating')->nullable();
            $table->string('experience_period', 50)->nullable();
            $table->string('status', 40)->default('pending');
            $table->string('verification_state', 40)->default('unverified');
            $table->boolean('institution_response_allowed')->default(true);
            $table->text('institution_response')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'status']);
            $table->index(['campus_id', 'status']);
            $table->index(['program_id', 'status']);
            $table->index(['reviewer_type', 'status']);
            $table->index(['target_type', 'status']);
            $table->index(['verification_state', 'status']);
        });

        Schema::create('review_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->string('dimension', 80);
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['review_id', 'dimension']);
            $table->index(['dimension', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_ratings');
        Schema::dropIfExists('reviews');
    }
};

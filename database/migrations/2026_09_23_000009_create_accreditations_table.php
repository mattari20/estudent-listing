<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accreditations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_offering_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 200);
            $table->string('recognition_type', 80);
            $table->string('authority_name', 200)->nullable();
            $table->string('scope_type', 80)->nullable();
            $table->string('reference_identifier', 120)->nullable();
            $table->string('status', 50)->default('candidate');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->date('last_verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['institution_id', 'status']);
            $table->index(['program_id', 'status']);
            $table->index(['program_offering_id', 'status']);
            $table->index(['campus_id', 'status']);
            $table->index(['recognition_type', 'status']);
            $table->index(['authority_name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accreditations');
    }
};

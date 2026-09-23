<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('assertions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evidence_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('attribute');
            $table->text('value')->nullable();
            $table->string('verification_state')->default('discovered');
            $table->decimal('confidence',5,4)->nullable();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['subject_type','subject_id']);
            $table->index(['attribute','verification_state']);
            $table->index(['valid_from','valid_until']);
        });
    }
    public function down(): void { Schema::dropIfExists('assertions'); }
};

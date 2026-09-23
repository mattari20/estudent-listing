<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('program_offerings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->string('delivery_mode')->default('on_campus');
            $table->string('status')->default('candidate');
            $table->string('intake_label')->nullable();
            $table->string('duration')->nullable();
            $table->string('availability')->default('unknown');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->timestamps();
            $table->index(['campus_id','status']);
            $table->index(['program_id','status']);
            $table->index(['delivery_mode','availability']);
        });
    }
    public function down(): void { Schema::dropIfExists('program_offerings'); }
};

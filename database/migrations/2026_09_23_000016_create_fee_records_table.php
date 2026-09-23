<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_offering_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_intake_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fee_type');
            $table->string('amount_type')->default('fixed');
            $table->decimal('amount',14,2)->nullable();
            $table->decimal('minimum_amount',14,2)->nullable();
            $table->decimal('maximum_amount',14,2)->nullable();
            $table->string('currency',3)->default('PKR');
            $table->string('frequency')->nullable();
            $table->string('status')->default('known');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['program_offering_id','fee_type']);
            $table->index(['admission_intake_id','status']);
            $table->index(['effective_from','effective_until']);
        });
    }
    public function down(): void { Schema::dropIfExists('fee_records'); }
};

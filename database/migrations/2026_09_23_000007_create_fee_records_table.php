<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_offering_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_intake_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fee_type', 80);
            $table->string('data_state', 40)->default('unknown');
            $table->decimal('amount_min', 14, 2)->nullable();
            $table->decimal('amount_max', 14, 2)->nullable();
            $table->char('currency', 3)->default('PKR');
            $table->string('unit', 40)->nullable();
            $table->string('period', 40)->nullable();
            $table->string('payment_stage', 60)->nullable();
            $table->string('applicant_category', 100)->nullable();
            $table->string('delivery_mode', 40)->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['institution_id', 'fee_type']);
            $table->index(['program_id', 'fee_type']);
            $table->index(['program_offering_id', 'fee_type']);
            $table->index(['admission_intake_id', 'fee_type']);
            $table->index(['data_state', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_records');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_offering_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_intake_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name', 200);
            $table->string('scholarship_type', 60)->nullable();
            $table->string('eligibility_basis', 100)->nullable();
            $table->text('eligibility_description')->nullable();
            $table->string('benefit_type', 80)->nullable();
            $table->decimal('amount_min', 14, 2)->nullable();
            $table->decimal('amount_max', 14, 2)->nullable();
            $table->char('currency', 3)->default('PKR');
            $table->string('benefit_frequency', 50)->nullable();
            $table->string('duration', 100)->nullable();
            $table->string('renewal_condition', 200)->nullable();
            $table->date('application_open')->nullable();
            $table->date('application_close')->nullable();
            $table->string('data_state', 40)->default('unknown');
            $table->string('status', 40)->default('candidate');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'status']);
            $table->index(['program_offering_id', 'status']);
            $table->index(['admission_intake_id', 'status']);
            $table->index(['data_state', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarships');
    }
};

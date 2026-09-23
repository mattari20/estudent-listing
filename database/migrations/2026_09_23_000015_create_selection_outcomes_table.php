<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('selection_outcomes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admission_intake_id')->constrained()->cascadeOnDelete();
            $table->string('outcome_type');
            $table->string('applicant_category')->nullable();
            $table->string('status')->default('published');
            $table->string('threshold_value')->nullable();
            $table->decimal('threshold_score',10,4)->nullable();
            $table->string('unit')->nullable();
            $table->unsignedInteger('list_number')->nullable();
            $table->text('notes')->nullable();
            $table->date('effective_date')->nullable();
            $table->timestamps();
            $table->index(['admission_intake_id','outcome_type']);
            $table->index(['applicant_category','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('selection_outcomes'); }
};

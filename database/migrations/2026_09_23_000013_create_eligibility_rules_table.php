<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('eligibility_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admission_intake_id')->constrained()->cascadeOnDelete();
            $table->string('rule_type');
            $table->string('applicant_category')->nullable();
            $table->string('qualification')->nullable();
            $table->text('requirement')->nullable();
            $table->decimal('minimum_marks',8,2)->nullable();
            $table->string('minimum_marks_type')->nullable();
            $table->string('required_subject')->nullable();
            $table->boolean('entry_test_required')->nullable();
            $table->string('domicile_requirement')->nullable();
            $table->unsignedInteger('minimum_age')->nullable();
            $table->unsignedInteger('maximum_age')->nullable();
            $table->text('experience_requirement')->nullable();
            $table->string('status')->default('current');
            $table->timestamps();
            $table->index(['admission_intake_id','rule_type']);
            $table->index(['applicant_category','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('eligibility_rules'); }
};

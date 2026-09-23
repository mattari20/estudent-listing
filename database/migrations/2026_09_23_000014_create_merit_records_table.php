<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('merit_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admission_intake_id')->constrained()->cascadeOnDelete();
            $table->string('stage')->default('published');
            $table->string('merit_type');
            $table->string('applicant_category')->nullable();
            $table->string('campus_context')->nullable();
            $table->string('value')->nullable();
            $table->decimal('score',10,4)->nullable();
            $table->decimal('position',12,4)->nullable();
            $table->string('unit')->nullable();
            $table->text('methodology')->nullable();
            $table->string('list_label')->nullable();
            $table->unsignedInteger('list_number')->nullable();
            $table->string('status')->default('current');
            $table->date('effective_date')->nullable();
            $table->timestamps();
            $table->index(['admission_intake_id','merit_type']);
            $table->index(['applicant_category','status']);
            $table->index(['stage','list_number']);
        });
    }
    public function down(): void { Schema::dropIfExists('merit_records'); }
};

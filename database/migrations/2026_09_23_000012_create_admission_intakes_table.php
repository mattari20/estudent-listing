<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('admission_intakes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_offering_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('status')->default('planned');
            $table->date('application_open_at')->nullable();
            $table->date('application_close_at')->nullable();
            $table->date('test_date')->nullable();
            $table->date('selection_date')->nullable();
            $table->date('enrollment_start_at')->nullable();
            $table->date('enrollment_end_at')->nullable();
            $table->unsignedInteger('seat_capacity')->nullable();
            $table->string('application_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['program_offering_id','status']);
            $table->index(['application_open_at','application_close_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('admission_intakes'); }
};

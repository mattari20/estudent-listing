<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('change_events', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('event_type');
            $table->string('materiality')->default('normal');
            $table->string('actor_type')->nullable();
            $table->string('status')->default('recorded');
            $table->text('summary')->nullable();
            $table->timestamp('detected_at')->nullable();
            $table->timestamps();
            $table->index(['subject_type','subject_id']);
            $table->index(['event_type','materiality']);
        });
    }
    public function down(): void { Schema::dropIfExists('change_events'); }
};

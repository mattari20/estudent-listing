<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('data_reverification_queue', function(Blueprint $table): void {
  $table->id(); $table->string('subject_type'); $table->unsignedBigInteger('subject_id'); $table->string('attribute')->nullable();
  $table->string('reason'); $table->string('priority')->default('normal'); $table->string('status')->default('open');
  $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete(); $table->unsignedBigInteger('evidence_id')->nullable();
  $table->timestamp('observed_at'); $table->timestamp('due_at')->nullable(); $table->string('assigned_to_type')->nullable(); $table->unsignedBigInteger('assigned_to_id')->nullable();
  $table->text('notes')->nullable(); $table->timestamp('resolved_at')->nullable(); $table->timestamps();
  $table->index(['subject_type','subject_id','status']); $table->index(['priority','status']); $table->index(['due_at','status']); $table->index(['source_id','status']);
 }); }
 public function down(): void { Schema::dropIfExists('data_reverification_queue'); }
};
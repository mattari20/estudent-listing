<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('import_conflicts', function(Blueprint $table): void {
  $table->id();
  $table->foreignId('import_row_id')->constrained()->cascadeOnDelete();
  $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
  $table->string('conflict_type'); $table->string('severity')->default('warning'); $table->string('status')->default('open');
  $table->text('description'); $table->json('details')->nullable();
  $table->unsignedBigInteger('related_row_id')->nullable();
  $table->string('related_subject_type')->nullable(); $table->unsignedBigInteger('related_subject_id')->nullable();
  $table->string('resolved_by_type')->nullable(); $table->unsignedBigInteger('resolved_by_id')->nullable();
  $table->text('resolution_reason')->nullable(); $table->timestamp('resolved_at')->nullable(); $table->timestamps();
  $table->index(['import_batch_id','status']); $table->index(['import_row_id','status']); $table->index(['conflict_type','status']);
 });
 }
 public function down(): void { Schema::dropIfExists('import_conflicts'); }
};
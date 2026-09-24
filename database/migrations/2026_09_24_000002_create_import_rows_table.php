<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('import_rows', function(Blueprint $table): void {
  $table->id(); $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete(); $table->unsignedInteger('row_number'); $table->string('status')->default('received'); $table->string('match_state')->default('unresolved'); $table->string('canonical_subject_type')->nullable(); $table->unsignedBigInteger('canonical_subject_id')->nullable(); $table->json('payload'); $table->json('normalized_payload')->nullable(); $table->json('validation_errors')->nullable(); $table->text('notes')->nullable(); $table->timestamps(); $table->unique(['import_batch_id','row_number']); $table->index(['import_batch_id','status']); $table->index(['canonical_subject_type','canonical_subject_id']);
 }); }
 public function down(): void { Schema::dropIfExists('import_rows'); }
};
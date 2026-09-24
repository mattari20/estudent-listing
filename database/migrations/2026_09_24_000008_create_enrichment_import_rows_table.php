<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('enrichment_import_rows', function(Blueprint $table): void {
  $table->id(); $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete(); $table->string('entity_type'); $table->unsignedBigInteger('canonical_subject_id')->nullable(); $table->string('status')->default('candidate'); $table->string('match_state')->default('unresolved'); $table->json('payload'); $table->json('normalized_payload')->nullable(); $table->json('validation_errors')->nullable(); $table->timestamps();
  $table->index(['import_batch_id','entity_type','status']); $table->index(['entity_type','canonical_subject_id']);
 }); }
 public function down(): void { Schema::dropIfExists('enrichment_import_rows'); }
};
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('import_batches', function(Blueprint $table): void {
  $table->id(); $table->string('batch_key')->unique(); $table->string('entity_type'); $table->string('source_type'); $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete(); $table->string('status')->default('received'); $table->unsignedInteger('total_rows')->default(0); $table->unsignedInteger('valid_rows')->default(0); $table->unsignedInteger('invalid_rows')->default(0); $table->unsignedInteger('candidate_rows')->default(0); $table->timestamp('received_at')->nullable(); $table->timestamp('completed_at')->nullable(); $table->text('notes')->nullable(); $table->timestamps(); $table->index(['entity_type','status']); $table->index(['source_id','status']);
 }); }
 public function down(): void { Schema::dropIfExists('import_batches'); }
};
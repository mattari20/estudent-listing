<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('import_reconciliation_events', function(Blueprint $table): void {
  $table->id(); $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete(); $table->foreignId('import_row_id')->nullable()->constrained()->nullOnDelete(); $table->string('event_type'); $table->string('state')->default('open'); $table->string('severity')->default('info'); $table->string('subject_type')->nullable(); $table->unsignedBigInteger('subject_id')->nullable(); $table->text('description')->nullable(); $table->json('metadata')->nullable(); $table->string('actor_type')->nullable(); $table->unsignedBigInteger('actor_id')->nullable(); $table->timestamp('occurred_at')->nullable(); $table->timestamps();
  $table->index(['import_batch_id','state']); $table->index(['import_row_id','event_type']); $table->index(['subject_type','subject_id','state']);
 }); }
 public function down(): void { Schema::dropIfExists('import_reconciliation_events'); }
};
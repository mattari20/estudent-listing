<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('source_retrieval_runs', function(Blueprint $table): void {
  $table->id(); $table->foreignId('source_id')->constrained()->cascadeOnDelete();
  $table->string('status')->default('started'); $table->unsignedSmallInteger('http_status')->nullable();
  $table->string('content_type')->nullable(); $table->unsignedBigInteger('content_length')->nullable();
  $table->string('content_hash')->nullable(); $table->foreignId('snapshot_id')->nullable()->constrained('source_snapshots')->nullOnDelete();
  $table->text('error_message')->nullable(); $table->timestamp('started_at'); $table->timestamp('completed_at')->nullable(); $table->timestamps();
  $table->index(['source_id','status']); $table->index(['status','started_at']);
 }); }
 public function down(): void { Schema::dropIfExists('source_retrieval_runs'); }
};
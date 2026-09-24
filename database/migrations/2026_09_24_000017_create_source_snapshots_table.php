<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('source_snapshots', function(Blueprint $table): void {
  $table->id(); $table->foreignId('source_id')->constrained()->cascadeOnDelete();
  $table->string('reference')->nullable(); $table->string('content_hash')->nullable(); $table->unsignedBigInteger('content_length')->nullable();
  $table->string('content_type')->nullable(); $table->timestamp('captured_at'); $table->string('status')->default('captured'); $table->json('metadata')->nullable(); $table->timestamps();
  $table->index(['source_id','captured_at']); $table->index(['source_id','content_hash']); $table->index(['status','captured_at']);
 }); }
 public function down(): void { Schema::dropIfExists('source_snapshots'); }
};
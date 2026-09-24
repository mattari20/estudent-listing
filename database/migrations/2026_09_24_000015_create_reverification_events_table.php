<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('reverification_events', function(Blueprint $table): void {
  $table->id(); $table->foreignId('queue_id')->constrained('data_reverification_queue')->cascadeOnDelete();
  $table->string('event_type'); $table->string('actor_type')->nullable(); $table->unsignedBigInteger('actor_id')->nullable();
  $table->string('previous_status')->nullable(); $table->string('new_status')->nullable(); $table->unsignedBigInteger('evidence_id')->nullable();
  $table->text('reason')->nullable(); $table->json('metadata')->nullable(); $table->timestamp('occurred_at'); $table->timestamps();
  $table->index(['queue_id','occurred_at']); $table->index(['event_type','occurred_at']); $table->index(['actor_type','actor_id']);
 }); }
 public function down(): void { Schema::dropIfExists('reverification_events'); }
};
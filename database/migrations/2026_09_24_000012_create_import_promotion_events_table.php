<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('import_promotion_events', function(Blueprint $table): void {
  $table->id(); $table->foreignId('import_row_id')->constrained()->cascadeOnDelete(); $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
  $table->string('entity_type'); $table->string('event_type'); $table->string('previous_status')->nullable(); $table->string('new_status')->nullable();
  $table->string('previous_match_state')->nullable(); $table->string('new_match_state')->nullable(); $table->string('canonical_subject_type')->nullable(); $table->unsignedBigInteger('canonical_subject_id')->nullable();
  $table->string('actor_type')->nullable(); $table->unsignedBigInteger('actor_id')->nullable(); $table->string('decision_type')->nullable(); $table->unsignedBigInteger('decision_id')->nullable();
  $table->text('reason')->nullable(); $table->json('metadata')->nullable(); $table->timestamp('occurred_at'); $table->timestamps();
  $table->index(['import_row_id','occurred_at']);
  $table->index(['import_batch_id','event_type']);
  $table->index(['canonical_subject_type','canonical_subject_id'], 'import_promo_canonical_subject_idx');
  $table->index(['actor_type','actor_id']);
 }); }
 public function down(): void { Schema::dropIfExists('import_promotion_events'); }
};
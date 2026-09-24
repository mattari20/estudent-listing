<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('verification_decisions', function(Blueprint $table): void {
  $table->id(); $table->foreignId('assertion_id')->constrained()->cascadeOnDelete(); $table->string('previous_state')->nullable(); $table->string('new_state'); $table->string('actor_type')->nullable(); $table->unsignedBigInteger('actor_id')->nullable(); $table->text('reason')->nullable(); $table->timestamp('decided_at')->nullable(); $table->timestamps();
  $table->index(['assertion_id','new_state']); $table->index(['actor_type','actor_id']);
 }); }
 public function down(): void { Schema::dropIfExists('verification_decisions'); }
};
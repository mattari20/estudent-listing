<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('source_change_assertion_links', function(Blueprint $table): void {
  $table->id();
  $table->unsignedBigInteger('source_change_observation_id');
  $table->foreign('source_change_observation_id', 'source_change_assertion_obs_fk')->references('id')->on('source_change_observations')->cascadeOnDelete();
  $table->foreignId('assertion_id')->constrained()->cascadeOnDelete();
  $table->string('attribute')->nullable(); $table->string('impact')->default('unknown'); $table->string('status')->default('candidate'); $table->text('reason')->nullable(); $table->timestamps();
  $table->unique(['source_change_observation_id','assertion_id'], 'source_change_assertion_unique');
  $table->index(['assertion_id','status']); $table->index(['impact','status']);
 }); }
 public function down(): void { Schema::dropIfExists('source_change_assertion_links'); }
};
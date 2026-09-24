<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('source_change_assertion_links', function(Blueprint $table): void {
  $table->id(); $table->foreignId('source_change_observation_id')->constrained()->cascadeOnDelete(); $table->foreignId('assertion_id')->constrained()->cascadeOnDelete();
  $table->string('attribute')->nullable(); $table->string('impact')->default('unknown'); $table->string('status')->default('candidate'); $table->text('reason')->nullable(); $table->timestamps();
  $table->unique(['source_change_observation_id','assertion_id']); $table->index(['assertion_id','status']); $table->index(['impact','status']);
 }); }
 public function down(): void { Schema::dropIfExists('source_change_assertion_links'); }
};
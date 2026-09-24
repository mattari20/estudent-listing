<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('provenance_review_events', function(Blueprint $table): void {
  $table->id(); $table->string('subject_type'); $table->unsignedBigInteger('subject_id'); $table->string('event_type'); $table->string('actor_type')->nullable(); $table->unsignedBigInteger('actor_id')->nullable(); $table->string('status')->default('recorded'); $table->text('reason')->nullable(); $table->json('metadata')->nullable(); $table->timestamp('occurred_at')->nullable(); $table->timestamps();
  $table->index(['subject_type','subject_id','event_type']); $table->index(['status','occurred_at']);
 }); }
 public function down(): void { Schema::dropIfExists('provenance_review_events'); }
};
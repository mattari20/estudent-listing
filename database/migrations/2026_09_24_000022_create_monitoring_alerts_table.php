<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('monitoring_alerts', function(Blueprint $table): void {
  $table->id(); $table->foreignId('source_monitoring_policy_id')->nullable()->constrained()->nullOnDelete();
  $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
  $table->string('alert_type'); $table->string('severity')->default('info'); $table->string('status')->default('open');
  $table->string('dedupe_key'); $table->text('message'); $table->json('details')->nullable();
  $table->timestamp('first_detected_at'); $table->timestamp('last_detected_at'); $table->timestamp('acknowledged_at')->nullable(); $table->timestamp('resolved_at')->nullable();
  $table->timestamps(); $table->index(['status','severity']); $table->index(['source_id','alert_type','status']); $table->index(['dedupe_key','status']);
 }); }
 public function down(): void { Schema::dropIfExists('monitoring_alerts'); }
};
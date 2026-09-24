<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('source_monitoring_policies', function(Blueprint $table): void {
  $table->string('cadence')->nullable(); $table->timestamp('next_run_at')->nullable(); $table->timestamp('last_success_at')->nullable(); $table->timestamp('last_failure_at')->nullable(); $table->unsignedInteger('consecutive_failures')->default(0); $table->index(['status','next_run_at']);
 }); }
 public function down(): void { Schema::table('source_monitoring_policies', function(Blueprint $table): void {
  $table->dropIndex(['status','next_run_at']); $table->dropColumn(['cadence','next_run_at','last_success_at','last_failure_at','consecutive_failures']);
 }); }
};
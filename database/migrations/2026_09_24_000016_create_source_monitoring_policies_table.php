<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('source_monitoring_policies', function(Blueprint $table): void {
  $table->id(); $table->string('name'); $table->string('subject_type')->nullable(); $table->string('attribute')->nullable();
  $table->unsignedInteger('fresh_after_days')->default(30); $table->unsignedInteger('stale_after_days')->default(90);
  $table->string('priority')->default('normal'); $table->string('status')->default('active');
  $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete(); $table->timestamp('last_run_at')->nullable(); $table->timestamps();
  $table->index(['status','subject_type']); $table->index(['source_id','status']);
 }); }
 public function down(): void { Schema::dropIfExists('source_monitoring_policies'); }
};
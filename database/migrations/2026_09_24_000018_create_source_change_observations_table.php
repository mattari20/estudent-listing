<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('source_change_observations', function(Blueprint $table): void {
  $table->id(); $table->foreignId('source_id')->constrained()->cascadeOnDelete(); $table->foreignId('previous_snapshot_id')->nullable()->constrained('source_snapshots')->nullOnDelete(); $table->foreignId('current_snapshot_id')->constrained('source_snapshots')->cascadeOnDelete();
  $table->string('change_state'); $table->string('materiality')->default('unknown'); $table->string('status')->default('detected'); $table->text('summary')->nullable(); $table->json('details')->nullable(); $table->timestamp('observed_at'); $table->timestamps();
  $table->index(['source_id','change_state','status']); $table->index(['current_snapshot_id']); $table->index(['materiality','status']);
 }); }
 public function down(): void { Schema::dropIfExists('source_change_observations'); }
};
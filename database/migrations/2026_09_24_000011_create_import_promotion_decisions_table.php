<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('import_promotion_decisions', function(Blueprint $table): void {
  $table->id(); $table->foreignId('import_row_id')->constrained()->cascadeOnDelete(); $table->string('decision'); $table->string('actor_type')->nullable(); $table->unsignedBigInteger('actor_id')->nullable(); $table->text('reason'); $table->timestamp('decided_at')->nullable(); $table->timestamps();
  $table->index(['import_row_id','decision']); $table->index(['actor_type','actor_id']);
 }); }
 public function down(): void { Schema::dropIfExists('import_promotion_decisions'); }
};
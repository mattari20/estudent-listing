<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('identity_resolution_decisions', function(Blueprint $table): void {
  $table->id(); $table->foreignId('import_row_id')->constrained()->cascadeOnDelete(); $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete(); $table->string('decision'); $table->string('actor_type')->nullable(); $table->unsignedBigInteger('actor_id')->nullable(); $table->text('reason')->nullable(); $table->timestamp('decided_at')->nullable(); $table->timestamps(); $table->index(['import_row_id','decision']); $table->index(['institution_id','decision']);
 }); }
 public function down(): void { Schema::dropIfExists('identity_resolution_decisions'); }
};
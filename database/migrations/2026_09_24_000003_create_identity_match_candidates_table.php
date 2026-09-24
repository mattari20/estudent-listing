<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('identity_match_candidates', function(Blueprint $table): void {
  $table->id(); $table->foreignId('import_row_id')->constrained()->cascadeOnDelete(); $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete(); $table->string('match_type'); $table->decimal('confidence',5,4)->nullable(); $table->string('status')->default('unresolved'); $table->text('reason')->nullable(); $table->timestamp('matched_at')->nullable(); $table->timestamps(); $table->index(['import_row_id','status']); $table->index(['institution_id','status']); $table->index(['match_type','status']);
 }); }
 public function down(): void { Schema::dropIfExists('identity_match_candidates'); }
};
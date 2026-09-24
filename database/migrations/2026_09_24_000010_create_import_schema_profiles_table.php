<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('import_schema_profiles', function(Blueprint $table): void {
  $table->id(); $table->string('profile_key'); $table->string('entity_type'); $table->string('version'); $table->string('status')->default('active'); $table->json('columns'); $table->json('required_columns'); $table->json('normalization_rules')->nullable(); $table->text('description')->nullable(); $table->timestamps();
  $table->unique(['profile_key','version']); $table->index(['entity_type','status']);
 }); }
 public function down(): void { Schema::dropIfExists('import_schema_profiles'); }
};
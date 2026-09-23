<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('release_records',function(Blueprint $table){$table->id();$table->string('version');$table->string('environment')->default('production');$table->string('status')->default('planned');$table->text('summary')->nullable();$table->text('rollback_notes')->nullable();$table->timestamp('released_at')->nullable();$table->timestamps();$table->index(['environment','status']);});}
 public function down(): void { Schema::dropIfExists('release_records'); }
};
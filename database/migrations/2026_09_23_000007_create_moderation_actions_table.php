<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('moderation_actions',function(Blueprint $table){$table->id();$table->string('target_type');$table->unsignedBigInteger('target_id');$table->string('action');$table->string('reason')->nullable();$table->string('actor_type')->nullable();$table->unsignedBigInteger('actor_id')->nullable();$table->text('notes')->nullable();$table->timestamps();$table->index(['target_type','target_id']);$table->index(['action','created_at']);});}
 public function down(): void { Schema::dropIfExists('moderation_actions'); }
};
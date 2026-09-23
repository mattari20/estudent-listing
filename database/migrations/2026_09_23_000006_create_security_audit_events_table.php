<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('security_audit_events',function(Blueprint $table){$table->id();$table->string('event_type');$table->string('actor_type')->nullable();$table->unsignedBigInteger('actor_id')->nullable();$table->string('subject_type')->nullable();$table->unsignedBigInteger('subject_id')->nullable();$table->string('severity')->default('info');$table->text('description')->nullable();$table->string('request_id')->nullable();$table->timestamps();$table->index(['event_type','severity']);$table->index(['subject_type','subject_id']);});}
 public function down(): void { Schema::dropIfExists('security_audit_events'); }
};
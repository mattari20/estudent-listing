<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('data_quality_events',function(Blueprint $table){
   $table->id(); $table->string('subject_type'); $table->unsignedBigInteger('subject_id')->nullable();
   $table->string('dimension'); $table->string('state')->default('open'); $table->string('severity')->default('info');
   $table->text('description')->nullable(); $table->timestamp('observed_at')->nullable(); $table->timestamp('resolved_at')->nullable(); $table->timestamps();
   $table->index(['subject_type','subject_id']); $table->index(['dimension','state']);
  });
 }
 public function down(): void { Schema::dropIfExists('data_quality_events'); }
};
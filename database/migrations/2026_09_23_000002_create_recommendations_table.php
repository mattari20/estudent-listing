<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('recommendations', function(Blueprint $table){
   $table->id();
   $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
   $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
   $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
   $table->string('contributor_type')->nullable();
   $table->string('recommendation')->nullable();
   $table->unsignedTinyInteger('rating')->nullable();
   $table->string('experience_period')->nullable();
   $table->string('verification_state')->default('unverified');
   $table->string('status')->default('pending');
   $table->text('comment')->nullable();
   $table->text('notes')->nullable();
   $table->timestamps();
   $table->index(['institution_id','status']);
   $table->index(['campus_id','status']);
   $table->index(['program_id','status']);
   $table->index(['contributor_type','verification_state']);
  });
 }
 public function down(): void { Schema::dropIfExists('recommendations'); }
};
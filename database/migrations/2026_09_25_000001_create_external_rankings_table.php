<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('external_rankings', function (Blueprint $table) {
   $table->id();
   $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
   $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
   $table->string('publisher_name');
   $table->string('ranking_name');
   $table->string('edition')->nullable();
   $table->text('methodology_summary')->nullable();
   $table->string('scope_type')->nullable();
   $table->string('geography_scope')->nullable();
   $table->string('discipline_scope')->nullable();
   $table->unsignedSmallInteger('year')->nullable();
   $table->unsignedInteger('rank_value')->nullable();
   $table->string('rank_label')->nullable();
   $table->decimal('score',12,4)->nullable();
   $table->decimal('percentile',7,4)->nullable();
   $table->string('signal_type')->default('ranking');
   $table->string('status')->default('candidate');
   $table->date('effective_from')->nullable();
   $table->date('effective_until')->nullable();
   $table->text('source_url')->nullable();
   $table->text('notes')->nullable();
   $table->timestamps();
   $table->index(['institution_id','status']);
   $table->index(['program_id','status']);
   $table->index(['publisher_name','ranking_name','year']);
   $table->index(['year','signal_type']);
  });
 }
 public function down(): void { Schema::dropIfExists('external_rankings'); }
};
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('ranking_methodologies',function(Blueprint $table){$table->id();$table->string('name');$table->string('version');$table->string('status')->default('draft');$table->string('scope')->nullable();$table->text('methodology_text')->nullable();$table->date('effective_from')->nullable();$table->date('effective_until')->nullable();$table->timestamps();$table->unique(['name','version']);});}
 public function down(): void { Schema::dropIfExists('ranking_methodologies'); }
};
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('institution_names', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_type');
            $table->string('language')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->index(['institution_id','name_type']);
            $table->index('name');
        });
    }
    public function down(): void { Schema::dropIfExists('institution_names'); }
};

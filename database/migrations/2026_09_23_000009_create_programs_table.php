<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('program_level')->nullable();
            $table->string('award')->nullable();
            $table->string('discipline')->nullable();
            $table->string('status')->default('candidate');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['institution_id','slug']);
            $table->index(['program_level','status']);
            $table->index('discipline');
        });
    }
    public function down(): void { Schema::dropIfExists('programs'); }
};

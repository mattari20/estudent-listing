<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type');
            $table->string('name');
            $table->text('url')->nullable();
            $table->string('authority_scope')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->index(['source_type','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('sources'); }
};

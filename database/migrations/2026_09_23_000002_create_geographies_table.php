<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('geographies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('geographies')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('type');
            $table->string('code')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['parent_id','slug']);
            $table->index(['type','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('geographies'); }
};

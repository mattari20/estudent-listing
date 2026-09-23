<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('institution_type')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->index(['status','name']);
        });
    }
    public function down(): void { Schema::dropIfExists('institutions'); }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('campuses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('geography_id')->nullable()->constrained('geographies')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('campus_type')->default('campus');
            $table->string('status')->default('candidate');
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('latitude',10,7)->nullable();
            $table->decimal('longitude',10,7)->nullable();
            $table->timestamps();
            $table->unique(['institution_id','slug']);
            $table->index(['geography_id','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('campuses'); }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->string('official_name')->nullable()->after('name');
            $table->string('institution_type')->nullable()->change();
            $table->string('status')->default('candidate')->change();
            $table->string('short_name')->nullable()->after('official_name');
            $table->text('description')->nullable()->after('short_name');
            $table->timestamp('verified_at')->nullable()->after('status');
        });
    }
    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropColumn(['official_name','short_name','description','verified_at']);
        });
    }
};

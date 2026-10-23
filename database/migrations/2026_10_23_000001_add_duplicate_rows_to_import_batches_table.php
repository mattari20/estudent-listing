<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('import_batches', 'duplicate_rows')) {
            Schema::table('import_batches', function (Blueprint $table): void {
                $table->unsignedInteger('duplicate_rows')->default(0)->after('candidate_rows');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('import_batches', 'duplicate_rows')) {
            Schema::table('import_batches', function (Blueprint $table): void {
                $table->dropColumn('duplicate_rows');
            });
        }
    }
};

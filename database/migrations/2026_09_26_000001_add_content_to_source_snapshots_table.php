<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('source_snapshots', 'content')) {
            return;
        }

        Schema::table('source_snapshots', function (Blueprint $table): void {
            $table->longText('content')->nullable()->after('content_type');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('source_snapshots', 'content')) {
            return;
        }

        Schema::table('source_snapshots', function (Blueprint $table): void {
            $table->dropColumn('content');
        });
    }
};

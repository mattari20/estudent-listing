<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evidences', function (Blueprint $table): void {
            $table->foreignId('snapshot_id')->nullable()->after('source_id')->constrained('source_snapshots')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('evidences', function (Blueprint $table): void {
            $table->dropForeign(['snapshot_id']);
            $table->dropColumn('snapshot_id');
        });
    }
};

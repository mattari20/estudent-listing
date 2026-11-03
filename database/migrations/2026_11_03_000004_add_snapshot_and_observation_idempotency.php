<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('source_change_observations')
            ->select('current_snapshot_id')
            ->groupBy('current_snapshot_id')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();

        if ($duplicates) {
            throw new RuntimeException(
                'Cannot add unique source observation identity: duplicate current_snapshot_id rows require reviewed reconciliation.'
            );
        }

        Schema::table('source_snapshots', function (Blueprint $table): void {
            $table->string('capture_key', 64)->nullable()->unique();
        });

        Schema::table('source_change_observations', function (Blueprint $table): void {
            $table->unique('current_snapshot_id', 'source_observations_current_snapshot_unique');
        });
    }

    public function down(): void
    {
        Schema::table('source_change_observations', function (Blueprint $table): void {
            $table->dropUnique('source_observations_current_snapshot_unique');
        });

        Schema::table('source_snapshots', function (Blueprint $table): void {
            $table->dropUnique(['capture_key']);
            $table->dropColumn('capture_key');
        });
    }
};

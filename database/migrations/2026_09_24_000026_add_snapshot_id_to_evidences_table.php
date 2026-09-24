<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evidence') && !Schema::hasTable('evidences')) {
            Schema::rename('evidence', 'evidences');
        }

        Schema::table('evidences', function (Blueprint $table): void {
            $table->foreignId('snapshot_id')
                ->nullable()
                ->after('source_id')
                ->constrained('source_snapshots')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('evidences')) {
            return;
        }

        if (Schema::hasColumn('evidences', 'snapshot_id')) {
            Schema::table('evidences', function (Blueprint $table): void {
                $table->dropForeign(['snapshot_id']);
                $table->dropColumn('snapshot_id');
            });
        }

        if (!Schema::hasTable('evidence')) {
            Schema::rename('evidences', 'evidence');
        }
    }
};

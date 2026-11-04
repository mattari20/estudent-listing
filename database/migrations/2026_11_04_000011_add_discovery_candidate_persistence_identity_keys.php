<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table): void {
            $table->string('discovery_identity_key', 64)->nullable()->unique('import_batches_discovery_identity_unique');
        });

        Schema::table('evidence', function (Blueprint $table): void {
            $table->string('discovery_identity_key', 64)->nullable()->unique('evidence_discovery_identity_unique');
        });

        Schema::table('import_rows', function (Blueprint $table): void {
            $table->string('discovery_identity_key', 64)->nullable()->unique('import_rows_discovery_identity_unique');
        });

        Schema::table('assertions', function (Blueprint $table): void {
            $table->string('discovery_identity_key', 64)->nullable()->unique('assertions_discovery_identity_unique');
        });
    }

    public function down(): void
    {
        Schema::table('assertions', function (Blueprint $table): void {
            $table->dropUnique('assertions_discovery_identity_unique');
            $table->dropColumn('discovery_identity_key');
        });

        Schema::table('import_rows', function (Blueprint $table): void {
            $table->dropUnique('import_rows_discovery_identity_unique');
            $table->dropColumn('discovery_identity_key');
        });

        Schema::table('evidence', function (Blueprint $table): void {
            $table->dropUnique('evidence_discovery_identity_unique');
            $table->dropColumn('discovery_identity_key');
        });

        Schema::table('import_batches', function (Blueprint $table): void {
            $table->dropUnique('import_batches_discovery_identity_unique');
            $table->dropColumn('discovery_identity_key');
        });
    }
};

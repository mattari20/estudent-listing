<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_events', function (Blueprint $table): void {
            $table->unsignedBigInteger('source_change_assertion_link_id')->nullable();
            $table->unique(
                'source_change_assertion_link_id',
                'change_events_source_link_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('change_events', function (Blueprint $table): void {
            $table->dropUnique('change_events_source_link_unique');
            $table->dropColumn('source_change_assertion_link_id');
        });
    }
};

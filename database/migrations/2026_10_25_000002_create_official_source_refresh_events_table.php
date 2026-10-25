<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_source_refresh_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->unsignedBigInteger('source_change_observation_id')->nullable()->index();
            $table->string('canonical_type', 100);
            $table->unsignedBigInteger('canonical_id');
            $table->string('field_name', 100);
            $table->json('before_value')->nullable();
            $table->json('after_value')->nullable();
            $table->text('evidence_url');
            $table->text('evidence_excerpt');
            $table->string('content_fingerprint', 128);
            $table->timestamp('observed_at');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['canonical_type', 'canonical_id', 'field_name'], 'official_refresh_canonical_field_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_source_refresh_events');
    }
};

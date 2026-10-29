<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structured_enrichment_promotion_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enrichment_import_row_id')
                ->constrained('enrichment_import_rows', indexName: 'struct_enrichment_row_fk')
                ->cascadeOnDelete();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->string('entity_type', 40);
            $table->string('event_type', 60);
            $table->string('canonical_type', 80)->nullable();
            $table->unsignedBigInteger('canonical_id')->nullable();
            $table->string('actor_type', 40)->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('before_snapshot')->nullable();
            $table->json('after_snapshot')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['enrichment_import_row_id', 'occurred_at'], 'structured_enrichment_row_event_idx');
            $table->index(['canonical_type', 'canonical_id'], 'structured_enrichment_canonical_idx');
            $table->index(['import_batch_id', 'event_type'], 'structured_enrichment_batch_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('structured_enrichment_promotion_events');
    }
};

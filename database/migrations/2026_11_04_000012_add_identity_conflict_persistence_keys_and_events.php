<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identity_match_candidates', function (Blueprint $table): void {
            $table->char('identity_key', 64)->nullable();
            $table->unique('identity_key', 'identity_match_candidates_identity_key_unique');
        });

        Schema::table('identity_resolution_decisions', function (Blueprint $table): void {
            $table->char('decision_identity_key', 64)->nullable();
            $table->unique('decision_identity_key', 'identity_resolution_decisions_identity_key_unique');
        });

        Schema::table('import_conflicts', function (Blueprint $table): void {
            $table->char('identity_key', 64)->nullable();
            $table->unique('identity_key', 'import_conflicts_identity_key_unique');
        });

        Schema::create('import_conflict_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_conflict_id')->constrained('import_conflicts')->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->string('status_before', 30)->nullable();
            $table->string('status_after', 30);
            $table->string('actor_type', 40)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->char('idempotency_key', 64)->unique();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['import_conflict_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_conflict_events');

        Schema::table('identity_resolution_decisions', function (Blueprint $table): void {
            $table->dropUnique('identity_resolution_decisions_identity_key_unique');
            $table->dropColumn('decision_identity_key');
        });

        Schema::table('import_conflicts', function (Blueprint $table): void {
            $table->dropUnique('import_conflicts_identity_key_unique');
            $table->dropColumn('identity_key');
        });

        Schema::table('identity_match_candidates', function (Blueprint $table): void {
            $table->dropUnique('identity_match_candidates_identity_key_unique');
            $table->dropColumn('identity_key');
        });
    }
};

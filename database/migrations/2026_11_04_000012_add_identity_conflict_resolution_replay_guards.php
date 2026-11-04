<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateMatch = DB::table('identity_match_candidates')
            ->select('import_row_id', 'institution_id')
            ->groupBy('import_row_id', 'institution_id')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicateMatch !== null) {
            throw new RuntimeException('Duplicate identity match candidates exist; reconcile them manually before adding the unique identity key.');
        }

        $duplicateOpenConflict = DB::table('import_conflicts')
            ->select('import_row_id', 'conflict_type')
            ->where('status', 'open')
            ->groupBy('import_row_id', 'conflict_type')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicateOpenConflict !== null) {
            throw new RuntimeException('Duplicate open import conflicts exist; reconcile them manually before adding the open-conflict key.');
        }

        Schema::table('identity_match_candidates', function (Blueprint $table): void {
            $table->char('identity_key', 64)->nullable();
        });

        DB::table('identity_match_candidates')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('identity_match_candidates')
                    ->where('id', $row->id)
                    ->update([
                        'identity_key' => hash('sha256', $row->import_row_id.'|'.($row->institution_id ?? 'null')),
                    ]);
            }
        });

        Schema::table('identity_match_candidates', function (Blueprint $table): void {
            $table->unique('identity_key', 'identity_match_candidates_identity_key_unique');
        });

        Schema::table('identity_resolution_decisions', function (Blueprint $table): void {
            $table->char('idempotency_key', 64)->nullable();
            $table->unique('idempotency_key', 'identity_resolution_decisions_idempotency_key_unique');
        });

        Schema::table('import_conflicts', function (Blueprint $table): void {
            $table->char('open_identity_key', 64)->nullable();
        });

        DB::table('import_conflicts')
            ->where('status', 'open')
            ->orderBy('id')
            ->chunkById(200, function ($conflicts): void {
                foreach ($conflicts as $conflict) {
                    DB::table('import_conflicts')
                        ->where('id', $conflict->id)
                        ->update([
                            'open_identity_key' => hash('sha256', $conflict->import_row_id.'|'.$conflict->conflict_type),
                        ]);
                }
            });

        Schema::table('import_conflicts', function (Blueprint $table): void {
            $table->unique('open_identity_key', 'import_conflicts_open_identity_key_unique');
        });

        Schema::create('import_conflict_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_conflict_id')->constrained('import_conflicts')->cascadeOnDelete();
            $table->foreignId('import_row_id')->constrained('import_rows')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->char('idempotency_key', 64)->unique();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['import_conflict_id', 'occurred_at'], 'import_conflict_events_history_idx');
            $table->index(['import_row_id', 'event_type'], 'import_conflict_events_row_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_conflict_events');

        Schema::table('import_conflicts', function (Blueprint $table): void {
            $table->dropUnique('import_conflicts_open_identity_key_unique');
            $table->dropColumn('open_identity_key');
        });

        Schema::table('identity_resolution_decisions', function (Blueprint $table): void {
            $table->dropUnique('identity_resolution_decisions_idempotency_key_unique');
            $table->dropColumn('idempotency_key');
        });

        Schema::table('identity_match_candidates', function (Blueprint $table): void {
            $table->dropUnique('identity_match_candidates_identity_key_unique');
            $table->dropColumn('identity_key');
        });
    }
};

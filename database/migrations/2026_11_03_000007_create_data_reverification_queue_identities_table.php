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
        $duplicates = DB::table('data_reverification_queue')
            ->select(['subject_type', 'subject_id', 'attribute'])
            ->selectRaw('COUNT(*) AS aggregate')
            ->where('status', 'open')
            ->groupBy(['subject_type', 'subject_id', 'attribute'])
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->first();

        if ($duplicates !== null) {
            throw new RuntimeException(
                'Cannot add active reverification queue identities: duplicate open subject/attribute rows exist. Reconcile them explicitly before retrying.'
            );
        }

        Schema::create('data_reverification_queue_identities', function (Blueprint $table): void {
            $table->string('identity_key', 64)->primary();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('attribute')->nullable();
            $table->unsignedBigInteger('active_queue_id')->nullable()->index();
            $table->timestamps();
        });

        DB::table('data_reverification_queue')
            ->where('status', 'open')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $identityKey = hash('sha256', json_encode([
                        $row->subject_type,
                        (int) $row->subject_id,
                        $row->attribute,
                    ], JSON_THROW_ON_ERROR));

                    DB::table('data_reverification_queue_identities')->insert([
                        'identity_key' => $identityKey,
                        'subject_type' => $row->subject_type,
                        'subject_id' => $row->subject_id,
                        'attribute' => $row->attribute,
                        'active_queue_id' => $row->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_reverification_queue_identities');
    }
};

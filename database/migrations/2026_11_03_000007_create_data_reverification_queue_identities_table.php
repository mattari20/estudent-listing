<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        if (! Schema::hasTable('data_reverification_queue_identities')) {
            Schema::create('data_reverification_queue_identities', function (Blueprint $table): void {
                $table->string('identity_key', 64)->primary();
                $table->string('subject_type');
                $table->unsignedBigInteger('subject_id');
                $table->string('attribute')->nullable();
                $table->unsignedBigInteger('active_queue_id')->nullable()->index();
                $table->timestamps();
            });
        } else {
            $requiredColumns = [
                'identity_key',
                'subject_type',
                'subject_id',
                'attribute',
                'active_queue_id',
                'created_at',
                'updated_at',
            ];
            $missingColumns = array_values(array_filter(
                $requiredColumns,
                static fn (string $column): bool => ! Schema::hasColumn('data_reverification_queue_identities', $column)
            ));

            if ($missingColumns !== []) {
                throw new RuntimeException(
                    'Existing data_reverification_queue_identities table is partially applied; missing columns: '.implode(', ', $missingColumns).'. Reconcile the exact schema before retrying.'
                );
            }

            $primaryKey = DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'data_reverification_queue_identities')
                ->where('INDEX_NAME', 'PRIMARY')
                ->orderBy('SEQ_IN_INDEX')
                ->pluck('COLUMN_NAME')
                ->all();

            if ($primaryKey !== ['identity_key']) {
                throw new RuntimeException(
                    'Existing data_reverification_queue_identities table has an incompatible primary key; expected PRIMARY(identity_key).'
                );
            }
        }

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

                    $expected = [
                        'identity_key' => $identityKey,
                        'subject_type' => $row->subject_type,
                        'subject_id' => $row->subject_id,
                        'attribute' => $row->attribute,
                        'active_queue_id' => $row->id,
                    ];

                    $existingByKey = DB::table('data_reverification_queue_identities')
                        ->where('identity_key', $identityKey)
                        ->first();

                    if ($existingByKey !== null) {
                        foreach ($expected as $column => $value) {
                            if ($existingByKey->{$column} != $value) {
                                throw new RuntimeException(
                                    'Existing reverification identity '.$identityKey.' conflicts with its source queue row; no automatic remapping is permitted.'
                                );
                            }
                        }

                        continue;
                    }

                    $existingByQueue = DB::table('data_reverification_queue_identities')
                        ->where('active_queue_id', $row->id)
                        ->first();

                    if ($existingByQueue !== null) {
                        throw new RuntimeException(
                            'Existing reverification identity for queue row '.$row->id.' has a different identity key; reconcile explicitly before retrying.'
                        );
                    }

                    DB::table('data_reverification_queue_identities')->insert([
                        ...$expected,
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

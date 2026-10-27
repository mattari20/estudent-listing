<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class OfficialSourceRefreshMigrationReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private const TABLE = 'official_source_refresh_events';

    private const MIGRATION = '2026_10_25_000002_create_official_source_refresh_events_table';

    public function test_equivalent_existing_table_is_not_recreated_when_migration_ledger_row_is_missing(): void
    {
        Schema::dropIfExists(self::TABLE);
        $this->createTable();
        DB::table('migrations')->where('migration', self::MIGRATION)->delete();

        $beforeColumns = Schema::getColumnListing(self::TABLE);
        $beforeIndexes = $this->indexMap();

        $migration = require base_path('database/migrations/'.self::MIGRATION.'.php');
        $migration->up();

        $this->assertSame($beforeColumns, Schema::getColumnListing(self::TABLE));
        $this->assertSame($beforeIndexes, $this->indexMap());
        $this->assertFalse(DB::table('migrations')->where('migration', self::MIGRATION)->exists());
        $this->assertSame(0, DB::table(self::TABLE)->count());
    }

    public function test_preexisting_table_with_missing_expected_index_fails_closed_without_schema_changes(): void
    {
        Schema::dropIfExists(self::TABLE);
        $this->createTable(['official_refresh_canonical_field_idx']);
        DB::table('migrations')->where('migration', self::MIGRATION)->delete();

        $beforeColumns = Schema::getColumnListing(self::TABLE);
        $beforeIndexes = $this->indexMap();

        try {
            $migration = require base_path('database/migrations/'.self::MIGRATION.'.php');
            $migration->up();
            $this->fail('A pre-existing schema missing a required index must not be adopted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('refusing migration-ledger reconciliation', $exception->getMessage());
            $this->assertStringContainsString('official_refresh_canonical_field_idx', $exception->getMessage());
        }

        $this->assertSame($beforeColumns, Schema::getColumnListing(self::TABLE));
        $this->assertSame($beforeIndexes, $this->indexMap());
        $this->assertFalse(DB::table('migrations')->where('migration', self::MIGRATION)->exists());
    }

    public function test_preexisting_table_missing_either_live_reported_index_fails_closed(): void
    {
        foreach (['official_refresh_source_idx', 'official_refresh_observation_idx'] as $missingIndex) {
            Schema::dropIfExists(self::TABLE);
            $this->createTable([$missingIndex]);
            DB::table('migrations')->where('migration', self::MIGRATION)->delete();
            $beforeColumns = Schema::getColumnListing(self::TABLE);
            $beforeIndexes = $this->indexMap();

            try {
                $migration = require base_path('database/migrations/'.self::MIGRATION.'.php');
                $migration->up();
                $this->fail('A schema missing '.$missingIndex.' must not be reconciled.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('refusing migration-ledger reconciliation', $exception->getMessage());
                $this->assertStringContainsString($missingIndex, $exception->getMessage());
            }

            $this->assertSame($beforeColumns, Schema::getColumnListing(self::TABLE));
            $this->assertSame($beforeIndexes, $this->indexMap());
            $this->assertFalse(DB::table('migrations')->where('migration', self::MIGRATION)->exists());
        }
    }

    public function test_preexisting_index_with_wrong_uniqueness_or_column_order_fails_closed(): void
    {
        foreach (['unique', 'wrong_columns'] as $definition) {
            Schema::dropIfExists(self::TABLE);
            $this->createTable();
            DB::table('migrations')->where('migration', self::MIGRATION)->delete();

            if ($definition === 'unique') {
                Schema::table(self::TABLE, function (Blueprint $table): void {
                    $table->dropIndex('official_refresh_source_idx');
                    $table->unique('source_id', 'official_refresh_source_idx');
                });
            } else {
                Schema::table(self::TABLE, function (Blueprint $table): void {
                    $table->dropIndex('official_refresh_canonical_field_idx');
                    $table->index(['canonical_id', 'canonical_type', 'field_name'], 'official_refresh_canonical_field_idx');
                });
            }

            $beforeColumns = Schema::getColumnListing(self::TABLE);
            $beforeIndexes = $this->indexMap();

            try {
                $migration = require base_path('database/migrations/'.self::MIGRATION.'.php');
                $migration->up();
                $this->fail('A pre-existing index with '.$definition.' must not be reconciled.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('refusing migration-ledger reconciliation', $exception->getMessage());
            }

            $this->assertSame($beforeColumns, Schema::getColumnListing(self::TABLE));
            $this->assertSame($beforeIndexes, $this->indexMap());
            $this->assertFalse(DB::table('migrations')->where('migration', self::MIGRATION)->exists());
        }
    }

    private function createTable(array $missingIndexes = []): void
    {
        Schema::create(self::TABLE, function (Blueprint $table) use ($missingIndexes): void {
            $table->id();
            $table->unsignedBigInteger('source_id')->nullable();
            if (! in_array('official_refresh_source_idx', $missingIndexes, true)) {
                $table->index('source_id', 'official_refresh_source_idx');
            }
            $table->unsignedBigInteger('source_change_observation_id')->nullable();
            if (! in_array('official_refresh_observation_idx', $missingIndexes, true)) {
                $table->index('source_change_observation_id', 'official_refresh_observation_idx');
            }
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

            if (! in_array('official_refresh_canonical_field_idx', $missingIndexes, true)) {
                $table->index(['canonical_type', 'canonical_id', 'field_name'], 'official_refresh_canonical_field_idx');
            }
        });
    }

    private function indexMap(): array
    {
        $indexes = [];
        foreach (Schema::getIndexes(self::TABLE) as $index) {
            $name = $index['name'] ?? null;
            if (is_string($name) && $name !== '') {
                $indexes[$name] = [
                    'columns' => array_values($index['columns'] ?? []),
                    'unique' => (bool) ($index['unique'] ?? false),
                ];
            }
        }
        ksort($indexes);

        return $indexes;
    }
}

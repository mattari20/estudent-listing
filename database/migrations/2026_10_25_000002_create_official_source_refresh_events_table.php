<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;
use RuntimeException;

return new class extends Migration
{
    private const TABLE = 'official_source_refresh_events';

    private const REQUIRED_INDEXES = [
        'official_refresh_source_idx' => ['source_id'],
        'official_refresh_observation_idx' => ['source_change_observation_id'],
        'official_refresh_canonical_field_idx' => ['canonical_type', 'canonical_id', 'field_name'],
    ];

    private const REQUIRED_COLUMNS = [
        'id',
        'source_id',
        'source_change_observation_id',
        'canonical_type',
        'canonical_id',
        'field_name',
        'before_value',
        'after_value',
        'evidence_url',
        'evidence_excerpt',
        'content_fingerprint',
        'observed_at',
        'occurred_at',
        'created_at',
        'updated_at',
    ];

    public function up(): void
    {
        // Production diagnostics have observed the exact table and columns while
        // the Laravel migrations ledger lacks this migration name. We may safely
        // reconcile that ledger only if the existing schema matches this migration's
        // expected columns and named indexes. Never recreate, alter, or silently
        // bless an incomplete pre-existing table.
        if (Schema::hasTable(self::TABLE)) {
            $missingColumns = array_values(array_filter(
                self::REQUIRED_COLUMNS,
                fn (string $column): bool => ! Schema::hasColumn(self::TABLE, $column)
            ));
            $missingIndexes = $this->missingIndexes();

            if ($missingColumns !== [] || $missingIndexes !== []) {
                throw new RuntimeException(
                    'Existing '.self::TABLE.' schema is inconsistent; refusing migration-ledger reconciliation. '
                    .'Missing columns: '.implode(', ', $missingColumns).'; '
                    .'missing or mismatched indexes: '.implode(', ', array_keys($missingIndexes)).'.'
                );
            }

            // Returning without schema changes lets Laravel's normal migrator
            // record this migration only after the controlled workflow has opted
            // in and verified the equivalent live schema.
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('source_id')->nullable()->index('official_refresh_source_idx');
            $table->unsignedBigInteger('source_change_observation_id')->nullable()->index('official_refresh_observation_idx');
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

    /**
     * @return array<string, array<int, string>>
     */
    private function missingIndexes(): array
    {
        if (! Schema::hasTable(self::TABLE)) {
            return self::REQUIRED_INDEXES;
        }

        $existingIndexes = [];
        foreach (Schema::getIndexes(self::TABLE) as $index) {
            $name = $index['name'] ?? null;
            if (is_string($name) && $name !== '') {
                $existingIndexes[$name] = array_values($index['columns'] ?? []);
            }
        }

        $missing = [];
        foreach (self::REQUIRED_INDEXES as $name => $columns) {
            if (($existingIndexes[$name] ?? null) !== $columns) {
                $missing[$name] = $columns;
            }
        }

        return $missing;
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};

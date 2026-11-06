<?php

declare(strict_types=1);

namespace App\Support\Database;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class MigrationSchemaGuard
{
    /**
     * Create a missing table, or fail closed when a partially-created table
     * does not contain every required column.
     *
     * @param list<string> $requiredColumns
     */
    public static function createTableIfMissing(string $table, Closure $definition, array $requiredColumns): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $definition);
            return;
        }

        $missingColumns = array_values(array_filter(
            $requiredColumns,
            static fn (string $column): bool => ! Schema::hasColumn($table, $column)
        ));

        if ($missingColumns !== []) {
            throw new RuntimeException(
                'Existing '.$table.' table is partially applied; missing columns: '.implode(', ', $missingColumns).'. Reconcile the exact schema before retrying.'
            );
        }
    }


    public static function ensureNullableUniqueString(
        string $table,
        string $column,
        int $length,
        string $indexName
    ): void {
        self::ensureNullableUniqueColumn($table, $column, $indexName, function () use ($table, $column, $length): void {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $length): void {
                $blueprint->string($column, $length)->nullable();
            });
        }, 'varchar('.$length.')');
    }

    public static function ensureNullableUniqueChar(
        string $table,
        string $column,
        int $length,
        string $indexName
    ): void {
        self::ensureNullableUniqueColumn($table, $column, $indexName, function () use ($table, $column, $length): void {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $length): void {
                $blueprint->char($column, $length)->nullable();
            });
        }, 'char('.$length.')');
    }

    public static function ensureNullableUniqueUnsignedBigInteger(
        string $table,
        string $column,
        string $indexName
    ): void {
        self::ensureNullableUniqueColumn($table, $column, $indexName, function () use ($table, $column): void {
            Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                $blueprint->unsignedBigInteger($column)->nullable();
            });
        }, 'bigint unsigned');
    }

    private static function ensureNullableUniqueColumn(
        string $table,
        string $column,
        string $indexName,
        callable $addColumn,
        string $expectedMysqlType
    ): void {
        if (! Schema::hasTable($table)) {
            throw new RuntimeException('Cannot add '.$table.'.'.$column.': prerequisite table is missing.');
        }

        if (! Schema::hasColumn($table, $column)) {
            $addColumn();
        } elseif (DB::getDriverName() === 'mysql') {
            $definition = DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $column)
                ->first();

            if (
                $definition === null
                || strtolower((string) $definition->COLUMN_TYPE) !== $expectedMysqlType
                || $definition->IS_NULLABLE !== 'YES'
            ) {
                throw new RuntimeException(
                    'Existing '.$table.'.'.$column.' has incompatible metadata; expected nullable '.$expectedMysqlType.'.'
                );
            }
        }

        $duplicates = DB::table($table)
            ->select($column)
            ->whereNotNull($column)
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();

        if ($duplicates) {
            throw new RuntimeException(
                'Cannot add unique identity index '.$indexName.': duplicate non-NULL values exist in '.$table.'.'.$column.'.'
            );
        }

        if (DB::getDriverName() === 'mysql') {
            $existing = DB::table('information_schema.STATISTICS')
                ->selectRaw('MIN(NON_UNIQUE) AS NON_UNIQUE, COUNT(*) AS COLUMN_COUNT, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS indexed_columns, MAX(SUB_PART) AS PREFIX_LENGTH, MIN(INDEX_TYPE) AS INDEX_TYPE, MAX(SEQ_IN_INDEX) AS LAST_SEQUENCE')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', $table)
                ->where('INDEX_NAME', $indexName)
                ->groupBy('INDEX_NAME')
                ->first();

            if ($existing !== null) {
                if (
                    (int) $existing->NON_UNIQUE !== 0
                    || (int) $existing->COLUMN_COUNT !== 1
                    || $existing->indexed_columns !== $column
                    || $existing->PREFIX_LENGTH !== null
                    || strtoupper((string) $existing->INDEX_TYPE) !== 'BTREE'
                    || (int) $existing->LAST_SEQUENCE !== 1
                ) {
                    throw new RuntimeException('Existing index '.$indexName.' has an incompatible definition.');
                }

                return;
            }

            // A composite unique index beginning with this column is not
            // equivalent: it still permits duplicate values for this column.
            $equivalent = DB::table('information_schema.STATISTICS')
                ->select('INDEX_NAME')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', $table)
                ->where('NON_UNIQUE', 0)
                ->groupBy('INDEX_NAME')
                ->havingRaw('COUNT(*) = 1')
                ->havingRaw(
                    'SUM(CASE WHEN COLUMN_NAME = ? AND SEQ_IN_INDEX = 1 AND SUB_PART IS NULL AND INDEX_TYPE = ? THEN 1 ELSE 0 END) = 1',
                    [$column, 'BTREE']
                )
                ->exists();
        } else {
            if (Schema::hasIndex($table, $indexName)) {
                return;
            }

            $equivalent = Schema::hasIndex($table, [$column], 'unique');
        }

        if ($equivalent) {
            throw new RuntimeException(
                'A unique index already protects '.$table.'.'.$column.' under a different name; reconcile the migration naming contract before continuing.'
            );
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $indexName): void {
            $blueprint->unique($column, $indexName);
        });
    }
}

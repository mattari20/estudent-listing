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
     * @param array<string, array{type: string|list<string>, nullable: bool, default?: mixed, auto_increment?: bool}> $expectedColumnDefinitions
     */
    public static function createTableIfMissing(string $table, Closure $definition, array $requiredColumns, array $expectedColumnDefinitions = []): void
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

        self::assertExistingColumnDefinitions($table, $expectedColumnDefinitions);
    }

    /**
     * @param array<string, array{type: string|list<string>, nullable: bool, default?: mixed, auto_increment?: bool}> $expected
     */
    public static function assertExistingColumnDefinitions(string $table, array $expected): void
    {
        if ($expected === []) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException(
                'Cannot verify exact existing '.$table.' column definitions on the unsupported migration driver.'
            );
        }

        $actualColumns = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->get()
            ->keyBy('COLUMN_NAME');

        foreach ($expected as $column => $contract) {
            $actual = $actualColumns->get($column);
            if ($actual === null) {
                throw new RuntimeException('Existing '.$table.'.'.$column.' is missing; expected column metadata was declared.');
            }

            $expectedTypes = is_array($contract['type']) ? $contract['type'] : [$contract['type']];
            $expectedTypes = array_map(self::normalizeMysqlColumnType(...), $expectedTypes);
            $actualType = self::normalizeMysqlColumnType((string) $actual->COLUMN_TYPE);

            if (! in_array($actualType, $expectedTypes, true)) {
                throw new RuntimeException(
                    'Existing '.$table.'.'.$column.' has incompatible type '.$actual->COLUMN_TYPE.'; expected '.implode(' or ', $expectedTypes).'.'
                );
            }

            $actualNullable = $actual->IS_NULLABLE === 'YES';
            if ($actualNullable !== $contract['nullable']) {
                throw new RuntimeException(
                    'Existing '.$table.'.'.$column.' has incompatible nullability; expected '.($contract['nullable'] ? 'nullable' : 'NOT NULL').'.'
                );
            }

            if (array_key_exists('default', $contract) && self::normalizeMysqlColumnDefault($actual->COLUMN_DEFAULT) !== self::normalizeMysqlColumnDefault($contract['default'])) {
                throw new RuntimeException(
                    'Existing '.$table.'.'.$column.' has an incompatible default value.'
                );
            }

            if (array_key_exists('auto_increment', $contract)) {
                $actualAutoIncrement = str_contains(strtolower((string) $actual->EXTRA), 'auto_increment');
                if ($actualAutoIncrement !== $contract['auto_increment']) {
                    throw new RuntimeException(
                        'Existing '.$table.'.'.$column.' has an incompatible auto-increment definition.'
                    );
                }
            }
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

    /**
     * MySQL 8 omits integer display widths while MariaDB 11.8 still reports
     * them in COLUMN_TYPE. Display width does not change the integer domain.
     */
    private static function normalizeMysqlColumnDefault(mixed $default): ?string
    {
        if ($default === null) {
            return null;
        }

        $value = (string) $default;
        if (strlen($value) >= 2 && $value[0] === "'" && $value[strlen($value) - 1] === "'") {
            $value = str_replace("''", "'", substr($value, 1, -1));
        }

        return $value;
    }

    public static function mysqlColumnTypeMatches(string $actual, string $expected): bool
    {
        return self::normalizeMysqlColumnType($actual) === self::normalizeMysqlColumnType($expected);
    }

    private static function normalizeMysqlColumnType(string $type): string
    {
        $type = strtolower(trim($type));

        return preg_replace('/^(tinyint|smallint|mediumint|int|integer|bigint)\\(\\d+\\)/', '$1', $type) ?? $type;
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
                || self::normalizeMysqlColumnType((string) $definition->COLUMN_TYPE) !== self::normalizeMysqlColumnType($expectedMysqlType)
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

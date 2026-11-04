<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyIdentityKeyInventoryService
{
    private const KEY_COLUMNS = [
        'identity_match_candidates' => 'identity_key',
        'identity_resolution_decisions' => 'decision_identity_key',
        'import_conflicts' => 'identity_key',
        'import_batches' => 'discovery_identity_key',
        'evidence' => 'discovery_identity_key',
        'import_rows' => 'discovery_identity_key',
        'assertions' => 'discovery_identity_key',
    ];

    /**
     * Read-only inventory. A NULL key is classified, never repaired or assigned.
     *
     * @return array{tables:array<int,array<string,mixed>>,totals:array<string,int>,mutations_performed:bool}
     */
    public function report(int $sampleLimit = 100): array
    {
        $sampleLimit = max(1, min(1000, $sampleLimit));
        $tables = [];
        $totals = ['records' => 0, 'keyed' => 0, 'legacy_null_key' => 0, 'not_inventoryable' => 0];

        foreach (self::KEY_COLUMNS as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                $tables[] = [
                    'table' => $table,
                    'column' => $column,
                    'status' => 'schema_unavailable',
                    'total' => 0,
                    'keyed' => 0,
                    'legacy_null_key' => 0,
                    'null_id_sample' => [],
                ];
                $totals['not_inventoryable']++;
                continue;
            }

            $total = DB::table($table)->count();
            $nullQuery = DB::table($table)->whereNull($column);
            $nullCount = (clone $nullQuery)->count();
            $keyedCount = $total - $nullCount;
            $nullIds = $nullQuery->orderBy('id')->limit($sampleLimit)->pluck('id')->map(fn ($id): int => (int) $id)->all();

            $tables[] = [
                'table' => $table,
                'column' => $column,
                'status' => $nullCount > 0 ? 'legacy_null_keys_present' : 'no_null_keys_observed',
                'total' => $total,
                'keyed' => $keyedCount,
                'legacy_null_key' => $nullCount,
                'null_id_sample' => $nullIds,
                'sample_truncated' => $nullCount > count($nullIds),
            ];

            $totals['records'] += $total;
            $totals['keyed'] += $keyedCount;
            $totals['legacy_null_key'] += $nullCount;
        }

        return [
            'tables' => $tables,
            'totals' => $totals,
            'mutations_performed' => false,
        ];
    }
}

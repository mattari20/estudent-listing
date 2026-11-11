<?php
declare(strict_types=1);

/**
 * Pure, fail-closed source-contract comparator.
 *
 * It deliberately refuses to infer equality when a source definition is
 * incomplete or metadata visibility is not proven.
 */
final class Phase1D2BSchemaContractComparator
{
    private const STATES = ['PASS', 'FAIL', 'MISSING', 'NOT_CHECKED', 'UNKNOWN'];

    public static function compareObject(
        string $kind,
        array $expected,
        ?array $observed,
        bool $metadataVisible = true
    ): array {
        $identity = (string)($expected['name'] ?? $expected['table'] ?? 'unnamed');
        $definitionState = strtoupper((string)($expected['definition_state'] ?? 'COMPLETE'));

        if ($definitionState !== 'COMPLETE') {
            return ['kind' => $kind, 'object' => $identity, 'state' => 'NOT_CHECKED', 'reason' => 'SOURCE_DEFINITION_NOT_COMPLETE'];
        }
        if (!$metadataVisible) {
            return ['kind' => $kind, 'object' => $identity, 'state' => 'UNKNOWN', 'reason' => 'METADATA_VISIBILITY_NOT_PROVEN'];
        }
        if ($observed === null) {
            return ['kind' => $kind, 'object' => $identity, 'state' => 'MISSING', 'reason' => 'OBJECT_ABSENT_WITH_COMPLETE_VISIBILITY'];
        }

        $expectedValue = self::canonical($kind, self::withoutControlFields($expected));
        $observedValue = self::canonical($kind, self::withoutControlFields($observed));
        $canonicalKind = match ($kind) {
            'columns' => 'column',
            'indexes' => 'index',
            'foreign_keys' => 'foreign_key',
            'triggers' => 'trigger',
            default => $kind,
        };
        if ($canonicalKind === 'column' && is_array($expectedValue['type'] ?? null)) {
            $allowedTypes = $expectedValue['type'];
            $actualType = $observedValue['type'] ?? null;
            unset($expectedValue['type'], $observedValue['type']);
            $matches = in_array($actualType, $allowedTypes, true) && $expectedValue === $observedValue;
        } else {
            $matches = $expectedValue === $observedValue;
        }

        return [
            'kind' => $kind,
            'object' => $identity,
            'state' => $matches ? 'PASS' : 'FAIL',
            'expected' => $expectedValue,
            'observed' => $observedValue,
        ];
    }

    public static function reconcileManifest(array $manifest, array $observed, array $visibility): array
    {
        $results = [];
        foreach (($manifest['tables'] ?? []) as $table => $contract) {
            $tableVisible = ($visibility['tables'] ?? false) === true;
            $actualTable = $observed['tables'][$table] ?? null;
            $tableContract = [
                'name' => $table,
                'definition_state' => $contract['definition_state'] ?? 'NOT_CHECKED',
                'engine' => $contract['table_options']['engine'] ?? null,
                'charset' => $contract['table_options']['charset'] ?? null,
                'collation' => $contract['table_options']['collation'] ?? null,
                'options' => $contract['table_options']['options'] ?? null,
            ];
            $tableComplete = ($contract['definition_state'] ?? 'NOT_CHECKED') === 'COMPLETE'
                && ($contract['table_options']['state'] ?? 'NOT_CHECKED') === 'COMPLETE';
            $tableContract['definition_state'] = $tableComplete ? 'COMPLETE' : 'NOT_CHECKED';
            $tableResult = self::compareObject('table', $tableContract, $actualTable, $tableVisible);
            $objects = ['table' => $tableResult];

            foreach (['columns', 'indexes', 'foreign_keys', 'triggers'] as $kind) {
                $visible = ($visibility[$kind] ?? false) === true;
                $actual = $observed[$kind][$table] ?? [];
                $expectedNames = [];
                foreach (($contract[$kind] ?? []) as $expectedObject) {
                    $objectState = strtoupper((string)($expectedObject['definition_state'] ?? $expectedObject['state'] ?? 'COMPLETE'));
                    $expectedObject['definition_state'] = $objectState;
                    $key = (string)($expectedObject['name'] ?? '');
                    $expectedNames[$key] = true;
                    $actualObject = $actual[$key] ?? null;
                    $objects[$kind][] = self::compareObject($kind, $expectedObject, $actualObject, $visible);
                }
                if ($visible) {
                    foreach ($actual as $actualName => $actualObject) {
                        if (!isset($expectedNames[$actualName])) {
                            $objects[$kind][] = [
                                'kind' => $kind,
                                'object' => (string)$actualName,
                                'state' => 'FAIL',
                                'reason' => 'UNEXPECTED_OBJECT_PRESENT',
                            ];
                        }
                    }
                }
            }
            $states = [];
            foreach ($objects as $group) {
                if (isset($group['state'])) $states[] = $group['state'];
                elseif (is_array($group)) foreach ($group as $row) $states[] = $row['state'];
            }
            $state = in_array('FAIL', $states, true) ? 'FAIL'
                : (in_array('MISSING', $states, true) ? 'FAIL'
                : (in_array('UNKNOWN', $states, true) ? 'UNKNOWN'
                : (in_array('NOT_CHECKED', $states, true) ? 'NOT_CHECKED' : 'PASS')));
            $results[$table] = ['table' => $table, 'state' => $state, 'objects' => $objects];
        }
        ksort($results, SORT_STRING);
        return [
            'contract' => 'phase1d2b-schema-comparison/v1',
            'state' => self::aggregate(array_column($results, 'state')),
            'table_count' => count($results),
            'complete_table_count' => count(array_filter($results, static fn(array $r): bool => $r['state'] === 'PASS')),
            'results' => $results,
        ];
    }

    public static function reconcileMigrationLedger(
        array $sourceMigrations,
        array $ledgerRows,
        array $migrationObjects,
        bool $ledgerVisible,
        bool $orderingVisible
    ): array {
        $sources = array_fill_keys(array_values(array_unique($sourceMigrations)), true);
        $grouped = [];
        foreach ($ledgerRows as $row) {
            $name = is_array($row) ? (string)($row['migration'] ?? '') : '';
            if ($name === '') continue;
            $grouped[$name][] = $row;
        }
        ksort($grouped, SORT_STRING);
        $missing = $unexpected = $duplicates = $invalid = [];
        foreach (array_keys($sources) as $name) {
            if (!isset($grouped[$name])) $missing[] = $name;
        }
        foreach ($grouped as $name => $rows) {
            if (!isset($sources[$name])) $unexpected[] = $name;
            if (count($rows) > 1) $duplicates[] = ['migration' => $name, 'count' => count($rows)];
            foreach ($rows as $row) {
                $batch = $row['batch'] ?? null;
                if (!is_numeric($batch) || (int)$batch < 1 || (string)(int)$batch !== (string)$batch) {
                    $invalid[] = ['migration' => $name, 'reason' => 'INVALID_BATCH'];
                }
            }
        }
        $batches = [];
        foreach ($ledgerRows as $row) {
            if (isset($row['batch']) && is_numeric($row['batch']) && (int)$row['batch'] > 0) $batches[] = (int)$row['batch'];
        }
        $distinct = array_values(array_unique($batches));
        sort($distinct, SORT_NUMERIC);
        $gaps = [];
        for ($i = 1; $i < count($distinct); $i++) {
            if ($distinct[$i] > $distinct[$i - 1] + 1) $gaps[] = [$distinct[$i - 1] + 1, $distinct[$i] - 1];
        }
        $orderingAnomalies = [];
        if ($ledgerVisible && $orderingVisible) {
            $byBatch = [];
            foreach ($ledgerRows as $row) {
                if (isset($row['batch'], $row['id']) && is_numeric($row['batch']) && is_numeric($row['id'])) {
                    $byBatch[(int)$row['batch']][] = $row;
                }
            }
            foreach ($byBatch as $batch => $batchRows) {
                usort($batchRows, static fn(array $a, array $b): int => (int)$a['id'] <=> (int)$b['id']);
                $orderedNames = array_map(static fn(array $row): string => (string)($row['migration'] ?? ''), $batchRows);
                $sortedNames = $orderedNames;
                sort($sortedNames, SORT_STRING);
                if ($orderedNames !== $sortedNames) $orderingAnomalies[] = ['batch' => $batch, 'reason' => 'MIGRATION_FILENAME_ORDER_DIFFERS_FROM_LEDGER_ID_ORDER'];
            }
        }
        $migrationResults = [];
        $names = array_keys($sources);
        sort($names, SORT_STRING);
        foreach ($names as $name) {
            $ledgerState = !$ledgerVisible ? 'UNKNOWN'
                : (!isset($grouped[$name]) ? 'MISSING'
                : (count($grouped[$name]) > 1 || in_array($name, array_column($invalid, 'migration'), true) ? 'FAIL' : 'PASS'));
            $objectStates = $migrationObjects[$name] ?? [];
            $objectState = $objectStates === [] ? 'NOT_CHECKED'
                : (in_array('FAIL', $objectStates, true) || in_array('MISSING', $objectStates, true) ? 'FAIL'
                : (in_array('UNKNOWN', $objectStates, true) ? 'UNKNOWN'
                : (in_array('NOT_CHECKED', $objectStates, true) ? 'NOT_CHECKED' : 'PASS')));
            $consistency = 'NOT_CHECKED';
            if ($ledgerState === 'UNKNOWN' || $objectState === 'UNKNOWN') $consistency = 'UNKNOWN';
            elseif ($ledgerState === 'FAIL' || $objectState === 'FAIL') $consistency = 'FAIL';
            elseif ($ledgerState === 'MISSING' && $objectState === 'PASS') $consistency = 'FAIL';
            elseif ($ledgerState === 'PASS' && $objectState === 'MISSING') $consistency = 'FAIL';
            elseif ($ledgerState === 'PASS' && $objectState === 'PASS') $consistency = 'PASS';
            $migrationResults[] = ['migration' => $name, 'ledger_state' => $ledgerState, 'object_state' => $objectState, 'consistency_state' => $consistency];
        }
        $overall = !$ledgerVisible ? 'UNKNOWN'
            : (($missing || $unexpected || $duplicates || $invalid) ? 'FAIL' : 'PASS');
        return [
            'state' => $overall,
            'source_count' => count($sources),
            'ledger_row_count' => count($ledgerRows),
            'source_files_not_recorded' => $ledgerVisible ? $missing : [],
            'ledger_entries_without_source_files' => $ledgerVisible ? $unexpected : [],
            'duplicate_ledger_entries' => $ledgerVisible ? $duplicates : [],
            'invalid_ledger_rows' => $ledgerVisible ? $invalid : [],
            'batch_numbers' => $ledgerVisible ? $distinct : [],
            'batch_gaps' => $ledgerVisible ? $gaps : [],
            'batch_gap_state' => $ledgerVisible ? 'NOT_CHECKED' : 'UNKNOWN',
            'ordering_state' => !$ledgerVisible || !$orderingVisible ? 'UNKNOWN' : ($orderingAnomalies === [] ? 'PASS' : 'FAIL'),
            'ordering_anomalies' => $ledgerVisible && $orderingVisible ? $orderingAnomalies : [],
            'migration_object_reconciliation' => $migrationResults,
        ];
    }

    public static function sanitizeDeterministically(array $evidence): array
    {
        $clean = self::sortRecursive($evidence);
        return $clean;
    }

    private static function aggregate(array $states): string
    {
        if (in_array('FAIL', $states, true)) return 'FAIL';
        if (in_array('MISSING', $states, true)) return 'FAIL';
        if (in_array('UNKNOWN', $states, true)) return 'UNKNOWN';
        if (in_array('NOT_CHECKED', $states, true)) return 'NOT_CHECKED';
        return 'PASS';
    }

    private static function withoutControlFields(array $value): array
    {
        unset($value['definition_state'], $value['state'], $value['source'], $value['source_provenance'], $value['definition_note']);
        return $value;
    }

    private static function canonical(string $kind, array $value): array
    {
        $kind = match ($kind) {
            'columns' => 'column',
            'indexes' => 'index',
            'foreign_keys' => 'foreign_key',
            'triggers' => 'trigger',
            default => $kind,
        };
        if ($kind === 'column') {
            unset($value['length']); // The full type already carries character/binary length.
        }
        if ($kind === 'table') {
            foreach (['engine', 'charset', 'collation'] as $key) if (isset($value[$key])) $value[$key] = strtolower((string)$value[$key]);
        }
        if ($kind === 'column' && isset($value['type'])) {
            $normalizeType = static function (string $type): string {
                $type = strtolower(preg_replace('/\\s+/', ' ', trim($type)));
                return preg_replace('/^(tinyint|smallint|mediumint|int|integer|bigint)\\(\\d+\\)/', '$1', $type) ?? $type;
            };
            $value['type'] = is_array($value['type'])
                ? array_values(array_unique(array_map($normalizeType, $value['type'])))
                : $normalizeType((string)$value['type']);
        }
        if ($kind === 'index') {
            if (isset($value['type'])) $value['type'] = strtoupper((string)$value['type']);
            if (isset($value['columns'])) foreach ($value['columns'] as &$column) if (isset($column['direction'])) $column['direction'] = strtoupper((string)$column['direction']);
            unset($column);
        }
        if ($kind === 'foreign_key') {
            foreach (['on_delete', 'on_update'] as $key) if (isset($value[$key])) $value[$key] = strtoupper((string)$value[$key]);
        }
        if ($kind === 'trigger') {
            foreach (['timing', 'event', 'orientation'] as $key) if (isset($value[$key])) $value[$key] = strtoupper((string)$value[$key]);
        }
        return self::sortRecursive($value);
    }

    private static function sortRecursive(array $value): array
    {
        foreach ($value as &$item) if (is_array($item)) $item = self::sortRecursive($item);
        unset($item);
        if (!array_is_list($value)) ksort($value, SORT_STRING);
        return $value;
    }
}

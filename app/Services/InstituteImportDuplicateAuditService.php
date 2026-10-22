<?php

namespace App\Services;

use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\InstitutionName;

class InstituteImportDuplicateAuditService
{
    /**
     * Read-only cross-batch audit. This report never deletes, rejects, promotes,
     * or publishes any row or canonical institution.
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $rows = ImportRow::query()
            ->with('batch:id,batch_key,entity_type,status')
            ->whereHas('batch', fn ($query) => $query->where('entity_type', 'institution'))
            ->orderBy('id')
            ->get();

        $canonicalByIdentity = [];
        $canonicalBySlug = [];
        foreach (Institution::query()->orderBy('id')->get(['id', 'name', 'official_name', 'short_name', 'slug', 'status']) as $institution) {
            foreach ([$institution->name, $institution->official_name, $institution->short_name] as $name) {
                $key = $this->normalize($name);
                if ($key !== '') {
                    $canonicalByIdentity[$key] ??= $institution;
                }
            }
            $slug = $this->normalize($institution->slug);
            if ($slug !== '') {
                $canonicalBySlug[$slug] ??= $institution;
            }
        }

        foreach (InstitutionName::query()->orderBy('id')->get(['institution_id', 'name']) as $alias) {
            $key = $this->normalize($alias->name);
            if ($key !== '' && ! isset($canonicalByIdentity[$key])) {
                $canonicalByIdentity[$key] = Institution::query()->find($alias->institution_id);
            }
        }

        $firstRowByIdentity = [];
        $items = [];
        $summary = [
            'rows_scanned' => $rows->count(),
            'duplicate_import_rows' => 0,
            'matches_existing_canonical' => 0,
            'incomplete_rows' => 0,
            'review_required' => 0,
        ];

        foreach ($rows as $row) {
            $payload = $row->normalized_payload ?? $row->payload ?? [];
            $name = trim((string) data_get($payload, 'name', data_get($payload, 'official_name', '')));
            $slug = trim((string) data_get($payload, 'slug', ''));
            $identityKeys = collect([
                $name,
                data_get($payload, 'official_name', ''),
                data_get($payload, 'short_name', ''),
            ])->map(fn ($value) => $this->normalize($value))->filter()->unique()->values();

            $issues = [];
            $matchedCanonical = null;
            foreach ($identityKeys as $key) {
                if (isset($canonicalByIdentity[$key])) {
                    $matchedCanonical = $canonicalByIdentity[$key];
                    break;
                }
            }
            if (! $matchedCanonical && $slug !== '') {
                $matchedCanonical = $canonicalBySlug[$this->normalize($slug)] ?? null;
            }

            $validationErrors = (array) ($row->validation_errors ?? []);
            $incomplete = false;

            if ($name === '') {
                $issues[] = 'missing_institution_name';
                $incomplete = true;
            }
            if ($row->status === 'invalid') {
                $issues[] = 'import_row_invalid';
                $incomplete = true;
            }
            if ($validationErrors !== []) {
                $issues[] = 'validation_errors_present';
                $incomplete = true;
            }
            if ($incomplete) {
                $summary['incomplete_rows']++;
            }

            $duplicateOf = null;
            foreach ($identityKeys as $key) {
                if (isset($firstRowByIdentity[$key])) {
                    $duplicateOf = $firstRowByIdentity[$key];
                    break;
                }
            }
            if (! $duplicateOf && $slug !== '') {
                $slugKey = 'slug:'.$this->normalize($slug);
                $duplicateOf = $firstRowByIdentity[$slugKey] ?? null;
            }

            if ($matchedCanonical) {
                $issues[] = 'matches_existing_canonical_institution';
                $summary['matches_existing_canonical']++;
            }
            if ($duplicateOf) {
                $issues[] = 'duplicate_import_row';
                $summary['duplicate_import_rows']++;
            }

            if ($identityKeys->isNotEmpty()) {
                foreach ($identityKeys as $key) {
                    $firstRowByIdentity[$key] ??= [
                        'row_id' => $row->id,
                        'batch_id' => $row->import_batch_id,
                        'row_number' => $row->row_number,
                        'name' => $name,
                    ];
                }
            }
            if ($slug !== '') {
                $firstRowByIdentity['slug:'.$this->normalize($slug)] ??= [
                    'row_id' => $row->id,
                    'batch_id' => $row->import_batch_id,
                    'row_number' => $row->row_number,
                    'name' => $name,
                ];
            }

            if ($issues === []) {
                continue;
            }
            $summary['review_required']++;
            $items[] = [
                'row_id' => $row->id,
                'batch_id' => $row->import_batch_id,
                'batch_key' => $row->batch?->batch_key,
                'row_number' => $row->row_number,
                'row_status' => $row->status,
                'institution_name' => $name !== '' ? $name : null,
                'issues' => array_values(array_unique($issues)),
                'validation_errors' => $validationErrors,
                'canonical_match' => $matchedCanonical ? [
                    'id' => $matchedCanonical->id,
                    'name' => $matchedCanonical->name,
                    'status' => $matchedCanonical->status,
                ] : null,
                'duplicate_of' => $duplicateOf,
                'recommended_action' => $name === ''
                    ? 'review_missing_data'
                    : ($duplicateOf ? 'review_duplicate_import_row' : ($matchedCanonical ? 'review_identity_match' : 'review_incomplete_or_conflicting_data')),
            ];
        }

        return [
            'scope' => 'read_only_cross_batch_institution_duplicate_audit',
            'summary' => $summary,
            'rows' => $items,
            'mutation_performed' => false,
            'warning' => 'This audit is a review aid. Do not delete or reject records automatically; verify the canonical match and preserve audit history.',
        ];
    }

    private function normalize(mixed $value): string
    {
        return mb_strtolower(trim((string) $value));
    }
}

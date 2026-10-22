<?php

namespace App\Services;

use App\Models\Assertion;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportBatch;
use App\Models\ImportConflict;
use App\Models\ImportRow;
use App\Models\Institution;

class InstituteImportRowDiagnosticService
{
    /**
     * Build a read-only, row-level explanation of import and publication blockers.
     *
     * @return array<string, mixed>
     */
    public function build(ImportBatch $batch): array
    {
        $batch->loadMissing('source');
        $rows = $batch->rows()->orderBy('row_number')->get();
        $items = [];
        $counts = [
            'total' => $rows->count(),
            'candidate' => 0,
            'invalid' => 0,
            'promoted' => 0,
            'published' => 0,
            'blocked' => 0,
            'ready_for_review' => 0,
        ];

        foreach ($rows as $row) {
            $decision = IdentityResolutionDecision::query()
                ->where('import_row_id', $row->id)
                ->latest('id')
                ->first();
            $evidenceActive = Assertion::query()
                ->where('subject_type', ImportRow::class)
                ->where('subject_id', $row->id)
                ->whereHas('evidence', fn ($query) => $query->where('status', 'active'))
                ->exists();
            $openConflicts = ImportConflict::query()
                ->where('import_row_id', $row->id)
                ->where('status', 'open')
                ->count();

            $canonical = null;
            if ($row->canonical_subject_type === Institution::class && $row->canonical_subject_id) {
                $canonical = Institution::query()->find($row->canonical_subject_id);
            }

            // Re-check against the current canonical directory, not just the state
            // that existed when this batch was originally imported. This catches stale
            // candidate rows duplicated by a later batch.
            $payload = $row->normalized_payload ?? $row->payload ?? [];
            $identityNames = collect([
                data_get($payload, 'name', ''),
                data_get($payload, 'official_name', ''),
                data_get($payload, 'short_name', ''),
            ])->map(fn ($value) => mb_strtolower(trim((string) $value)))
                ->filter()
                ->unique()
                ->values();

            $possibleDuplicate = null;
            if ($row->status === 'candidate' && $identityNames->isNotEmpty()) {
                $possibleDuplicate = Institution::query()
                    ->where(function ($query) use ($identityNames, $payload): void {
                        $slug = trim((string) data_get($payload, 'slug', ''));
                        if ($slug !== '') {
                            $query->where('slug', $slug);
                        }
                        foreach ($identityNames as $name) {
                            foreach (['name', 'official_name', 'short_name'] as $field) {
                                $query->orWhereRaw('LOWER(TRIM('.$field.')) = ?', [$name]);
                            }
                        }
                    })
                    ->first();

                if (! $possibleDuplicate) {
                    $aliasInstitutionId = \App\Models\InstitutionName::query()
                        ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(name))'), $identityNames->all())
                        ->value('institution_id');
                    if ($aliasInstitutionId) {
                        $possibleDuplicate = Institution::query()->find($aliasInstitutionId);
                    }
                }
            }

            $blockers = [];
            if ($possibleDuplicate) {
                $blockers[] = 'canonical_duplicate_requires_match';
            }
            if ($batch->status !== 'validated') {
                $blockers[] = 'batch_not_validated';
            }
            if ($batch->source?->status !== 'active') {
                $blockers[] = 'source_not_active';
            }
            if ($row->status === 'invalid') {
                $blockers[] = 'row_validation_failed';
            }
            if ($row->status === 'candidate' && ! in_array($decision?->decision, ['approved_new', 'approved_match'], true)) {
                $blockers[] = 'identity_decision_required';
            }
            if (in_array($row->status, ['candidate', 'promoted'], true) && ! $evidenceActive) {
                $blockers[] = 'active_source_evidence_required';
            }
            if ($openConflicts > 0) {
                $blockers[] = 'open_conflicts';
            }
            if ($row->status === 'promoted' && ! $canonical) {
                $blockers[] = 'canonical_institution_missing';
            } elseif ($row->status === 'promoted' && $canonical && $canonical->status !== 'active') {
                $blockers[] = 'publication_required';
            }

            $counts[$row->status] = ($counts[$row->status] ?? 0) + 1;
            if ($row->status === 'promoted' && $canonical?->status === 'active') {
                $counts['published']++;
            }
            if ($blockers !== []) {
                $counts['blocked']++;
            } elseif ($row->status === 'candidate') {
                $counts['ready_for_review']++;
            }

            $items[] = [
                'row_id' => $row->id,
                'row_number' => $row->row_number,
                'institution_name' => data_get($row->normalized_payload, 'name', data_get($row->payload, 'name')),
                'row_status' => $row->status,
                'match_state' => $row->match_state,
                'identity_decision' => $decision?->decision,
                'evidence_active' => $evidenceActive,
                'open_conflicts' => $openConflicts,
                'canonical_institution_id' => $canonical?->id,
                'canonical_status' => $canonical?->status,
                'possible_duplicate_institution_id' => $possibleDuplicate?->id,
                'possible_duplicate_institution_name' => $possibleDuplicate?->name,
                'possible_duplicate_institution_status' => $possibleDuplicate?->status,
                'validation_errors' => $row->validation_errors ?? [],
                'blockers' => array_values(array_unique($blockers)),
            ];
        }

        return [
            'scope' => 'read_only_institution_import_row_diagnostics',
            'batch' => [
                'id' => $batch->id,
                'batch_key' => $batch->batch_key,
                'status' => $batch->status,
                'source_id' => $batch->source_id,
                'source_status' => $batch->source?->status,
            ],
            'summary' => $counts,
            'rows' => $items,
            'mutation_performed' => false,
        ];
    }
}

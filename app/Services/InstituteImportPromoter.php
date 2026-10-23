<?php

namespace App\Services;

use App\Models\IdentityMatchCandidate;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportConflict;
use App\Models\ImportRow;
use App\Models\Assertion;
use App\Models\Institution;
use App\Models\InstitutionName;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InstituteImportPromoter
{
    public function promote(ImportRow $row, IdentityResolutionDecision $decision): Institution
    {
        return DB::transaction(fn (): Institution => $this->promoteWithinTransaction($row, $decision));
    }

    private function promoteWithinTransaction(ImportRow $row, IdentityResolutionDecision $decision): Institution
    {
        if ($decision->import_row_id !== $row->id) {
            throw new RuntimeException('Resolution decision does not belong to import row.');
        }

        $row->loadMissing('batch.source');

        if ($row->status !== 'candidate') {
            throw new RuntimeException('Only candidate import rows can be promoted.');
        }

        if ($row->batch?->status !== 'validated') {
            throw new RuntimeException('Only rows from a validated import batch can be promoted.');
        }

        if ($row->batch?->source?->status !== 'active') {
            throw new RuntimeException('An active source record is required before promotion.');
        }

        $hasActiveEvidence = Assertion::query()
            ->where('subject_type', ImportRow::class)
            ->where('subject_id', $row->id)
            ->whereHas('evidence', fn ($query) => $query->where('status', 'active'))
            ->exists();

        if (! $hasActiveEvidence) {
            throw new RuntimeException('Active source evidence is required before promotion.');
        }

        if (! in_array($decision->decision, ['approved_new', 'approved_match'], true)) {
            throw new RuntimeException('Only an explicit approved resolution decision can promote an import row.');
        }

        if (ImportConflict::where('import_row_id', $row->id)->where('status', 'open')->exists()) {
            throw new RuntimeException('Open import conflicts must be explicitly resolved before promotion.');
        }

        $payload = $row->normalized_payload ?? $row->payload ?? [];

        if (trim((string) ($payload['name'] ?? '')) === '') {
            throw new RuntimeException('Institution name is required.');
        }

        if ($decision->decision === 'approved_match') {
            $institution = Institution::findOrFail($decision->institution_id);
            $previousStatus = $row->status;
            $previousMatch = $row->match_state;

            $row->update([
                'status' => 'promoted',
                'match_state' => 'matched',
                'canonical_subject_type' => Institution::class,
                'canonical_subject_id' => $institution->id,
            ]);

            app(ImportPromotionAuditService::class)->record(
                $row,
                'promotion_succeeded',
                $decision->actor_id,
                'Existing canonical institution matched.',
                [
                    'previous_status' => $previousStatus,
                    'previous_match_state' => $previousMatch,
                    'created_canonical' => false,
                    'promotion_mode' => 'approved_match',
                ],
                'identity_resolution',
                $decision->id,
            );

            return $institution;
        }

        // A new-institution decision must not bypass unresolved canonical identity candidates.
        if (IdentityMatchCandidate::query()
            ->where('import_row_id', $row->id)
            ->where('status', 'candidate')
            ->exists()) {
            throw new RuntimeException(
                'Unresolved identity match candidates exist. Review and resolve identity candidates before approving a new institution.'
            );
        }

        $slug = trim((string) ($payload['slug'] ?? '')) ?: Str::slug((string) $payload['name']);
        $institutionType = trim((string) ($payload['institution_type'] ?? ''));

        // Never create a second canonical institution when an exact normalized identity
        // already exists. An explicit approved_match decision must be used instead.
        $identityNames = collect([
            $payload['name'] ?? '',
            $payload['official_name'] ?? '',
            $payload['short_name'] ?? '',
        ])->map(fn ($value) => Str::lower(trim((string) $value)))
            ->filter()
            ->unique()
            ->values();

        $existingInstitution = Institution::query()
            ->where(function ($query) use ($slug, $identityNames): void {
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

        if ($existingInstitution) {
            throw new RuntimeException(
                'A matching canonical institution already exists; explicit approved_match identity resolution is required.'
            );
        }

        $aliasMatch = InstitutionName::query()
            ->whereIn(DB::raw('LOWER(TRIM(name))'), $identityNames->all())
            ->exists();

        if ($aliasMatch) {
            throw new RuntimeException(
                'A matching canonical institution alias already exists; explicit approved_match identity resolution is required.'
            );
        }

        $campusSignals = collect([
            $institutionType,
            $payload['name'] ?? '',
            $payload['official_name'] ?? '',
            $payload['short_name'] ?? '',
            $slug,
        ])->contains(fn ($value) => preg_match('/(^|[^a-z])campus([^a-z]|$)/i', trim((string) $value)) === 1);

        if ($campusSignals) {
            throw new RuntimeException(
                'This candidate appears to describe a campus, not a standalone institution. Resolve its parent institution and import it as a campus instead of creating a new institution.'
            );
        }

        $institution = Institution::create([
            'name' => $payload['name'],
            'official_name' => $payload['official_name'] ?? null,
            'short_name' => $payload['short_name'] ?? null,
            'slug' => $slug,
            'institution_type' => $institutionType !== '' ? $institutionType : null,
            // Imported institutions must remain unpublished until the explicit publication workflow.
            // Never trust a source payload to activate a canonical institution during promotion.
            'status' => 'candidate',
        ]);

        InstitutionName::create([
            'institution_id' => $institution->id,
            'name' => $institution->name,
            'name_type' => 'official',
            'is_current' => true,
        ]);

        $previousStatus = $row->status;
        $previousMatch = $row->match_state;

        $row->update([
            'status' => 'promoted',
            'match_state' => 'new_canonical',
            'canonical_subject_type' => Institution::class,
            'canonical_subject_id' => $institution->id,
        ]);

        app(ImportPromotionAuditService::class)->record(
            $row,
            'promotion_succeeded',
            $decision->actor_id,
            'New canonical institution created.',
            [
                'previous_status' => $previousStatus,
                'previous_match_state' => $previousMatch,
                'created_canonical' => true,
                'promotion_mode' => 'approved_new',
            ],
            'identity_resolution',
            $decision->id,
        );

        return $institution;
    }
}

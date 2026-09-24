<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\Geography;
use App\Models\Institution;
use Illuminate\Support\Collection;

class CampusRelationshipIntegrityService
{
    /**
     * Return read-only integrity findings for an active campus.
     *
     * This service never repairs, reassigns, activates, deactivates, or deletes
     * canonical campus, institution, or geography records.
     */
    public function inspect(Campus $campus): array
    {
        if ($campus->status !== 'active') {
            return [];
        }

        $findings = [];

        if ($campus->institution_id === null) {
            $findings[] = 'missing_institution';
        } elseif (Institution::query()
            ->whereKey($campus->institution_id)
            ->where('status', 'active')
            ->exists() === false) {
            $findings[] = 'institution_not_active';
        }

        if (trim((string) $campus->name) === '') {
            $findings[] = 'missing_name';
        }

        if (trim((string) $campus->slug) === '') {
            $findings[] = 'missing_slug';
        } elseif (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $campus->slug) === 0) {
            $findings[] = 'invalid_slug';
        }

        if ($campus->geography_id === null) {
            $findings[] = 'missing_geography';
        } elseif (Geography::query()
            ->whereKey($campus->geography_id)
            ->where('status', 'active')
            ->exists() === false) {
            $findings[] = 'geography_not_active';
        }

        if ($campus->institution_id !== null && trim((string) $campus->slug) !== '') {
            $duplicateCount = Campus::query()
                ->where('institution_id', $campus->institution_id)
                ->where('slug', $campus->slug)
                ->whereKeyNot($campus->getKey())
                ->count();

            if ($duplicateCount > 0) {
                $findings[] = 'duplicate_institution_slug';
            }
        }

        return $findings;
    }

    /**
     * Inspect a collection without changing its contents.
     *
     * Collection-level duplicate detection also works with unsaved campuses,
     * which keeps the diagnostic boundary independent of database uniqueness.
     *
     * @return array<int, array{campus_id:int|string|null, findings:array<int,string>}>
     */
    public function inspectMany(Collection $campuses): array
    {
        $active = $campuses
            ->filter(fn (Campus $campus) => $campus->status === 'active')
            ->values();

        $identityCounts = $active
            ->filter(fn (Campus $campus) => $campus->institution_id !== null && trim((string) $campus->slug) !== '')
            ->countBy(fn (Campus $campus) => sprintf('%s:%s', $campus->institution_id, $campus->slug));

        return $active
            ->map(function (Campus $campus) use ($identityCounts): array {
                $findings = $this->inspect($campus);
                $identity = $campus->institution_id . ':' . $campus->slug;

                if (
                    $campus->institution_id !== null
                    && trim((string) $campus->slug) !== ''
                    && ($identityCounts[$identity] ?? 0) > 1
                    && in_array('duplicate_institution_slug', $findings, true) === false
                ) {
                    $findings[] = 'duplicate_institution_slug';
                }

                return [
                    'campus_id' => $campus->getKey(),
                    'findings' => $findings,
                ];
            })
            ->filter(fn (array $result) => $result['findings'] !== [])
            ->values()
            ->all();
    }
}

<?php

namespace App\Services;

use App\Models\Institution;
use Illuminate\Support\Collection;

class InstitutionIdentityIntegrityService
{
    /**
     * Return read-only identity findings for active institutions.
     *
     * Findings are diagnostic only. This service never repairs or mutates
     * canonical institution data.
     */
    public function inspect(Institution $institution): array
    {
        if ($institution->status !== 'active') {
            return [];
        }

        $findings = [];

        if (trim((string) $institution->name) === '') {
            $findings[] = 'missing_name';
        }

        if (trim((string) $institution->slug) === '') {
            $findings[] = 'missing_slug';
        } elseif (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $institution->slug)) {
            $findings[] = 'invalid_slug';
        }

        if ($institution->slug !== null && $institution->slug !== '') {
            $duplicateSlugCount = Institution::query()
                ->where('status', 'active')
                ->where('slug', $institution->slug)
                ->whereKeyNot($institution->getKey())
                ->count();

            if ($duplicateSlugCount > 0) {
                $findings[] = 'duplicate_active_slug';
            }
        }

        return $findings;
    }

    /**
     * Inspect a collection of institutions without changing its contents.
     *
     * @return array<int, array{institution_id:int|string, findings:array<int,string>}>
     */
    public function inspectMany(Collection $institutions): array
    {
        return $institutions
            ->filter(fn (Institution $institution) => $institution->status === 'active')
            ->map(function (Institution $institution): array {
                return [
                    'institution_id' => $institution->getKey(),
                    'findings' => $this->inspect($institution),
                ];
            })
            ->filter(fn (array $result) => $result['findings'] !== [])
            ->values()
            ->all();
    }
}

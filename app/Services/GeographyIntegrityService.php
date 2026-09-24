<?php

namespace App\Services;

use App\Models\Geography;
use Illuminate\Support\Collection;

class GeographyIntegrityService
{
    public function inspect(Geography $geography): array
    {
        if ($geography->status !== 'active') {
            return [];
        }

        $findings = [];

        if (trim((string) $geography->name) === '') {
            $findings[] = 'missing_name';
        }

        if (trim((string) $geography->slug) === '') {
            $findings[] = 'missing_slug';
        } elseif (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $geography->slug) === 0) {
            $findings[] = 'invalid_slug';
        }

        if ($geography->type === 'country') {
            if ($geography->parent_id !== null) {
                $findings[] = 'country_has_parent';
            }
        } elseif ($geography->parent_id === null) {
            $findings[] = 'non_country_without_parent';
        } elseif (
            Geography::query()
                ->whereKey($geography->parent_id)
                ->where('status', 'active')
                ->exists() === false
        ) {
            $findings[] = 'parent_not_active';
        }

        if ($geography->parent_id !== null && (int) $geography->parent_id === (int) $geography->getKey()) {
            $findings[] = 'self_parent';
        }

        if ($geography->parent_id !== null && trim((string) $geography->slug) !== '') {
            $duplicateCount = Geography::query()
                ->where('parent_id', $geography->parent_id)
                ->where('slug', $geography->slug)
                ->whereKeyNot($geography->getKey())
                ->count();

            if ($duplicateCount > 0) {
                $findings[] = 'duplicate_parent_slug';
            }
        }

        return $findings;
    }

    public function inspectMany(Collection $geographies): array
    {
        $active = $geographies
            ->filter(fn (Geography $geography) => $geography->status === 'active')
            ->values();

        $identityCounts = $active
            ->filter(fn (Geography $geography) => $geography->parent_id !== null && trim((string) $geography->slug) !== '')
            ->countBy(fn (Geography $geography) => sprintf('%s:%s', $geography->parent_id, $geography->slug));

        return $active
            ->map(function (Geography $geography) use ($identityCounts): array {
                $findings = $this->inspect($geography);
                $identity = sprintf('%s:%s', $geography->parent_id, $geography->slug);

                if (
                    $geography->parent_id !== null
                    && trim((string) $geography->slug) !== ''
                    && ($identityCounts[$identity] ?? 0) > 1
                    && in_array('duplicate_parent_slug', $findings, true) === false
                ) {
                    $findings[] = 'duplicate_parent_slug';
                }

                return [
                    'geography_id' => $geography->getKey(),
                    'findings' => $findings,
                ];
            })
            ->filter(fn (array $result) => $result['findings'] !== [])
            ->values()
            ->all();
    }

    public function validate(): array
    {
        $errors = [];

        foreach ($this->inspectMany(Geography::query()->get()) as $result) {
            foreach ($result['findings'] as $finding) {
                $errors[] = $finding . ':' . $result['geography_id'];
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }
}

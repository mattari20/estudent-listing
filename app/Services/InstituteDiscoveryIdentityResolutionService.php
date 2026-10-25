<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\Institution;
use App\Models\Program;
use Illuminate\Support\Str;

/**
 * Conservative identity resolution for discovery candidates.
 *
 * Exact normalized keys are used only within the declared parent institution.
 * Similarity is deliberately not used to auto-merge entities.
 */
class InstituteDiscoveryIdentityResolutionService
{
    /**
     * @return array{state:string,entity_type:string,canonical_id:?int,normalized_key:string,reason:string}
     */
    public function resolve(string $entityType, int $institutionId, string $name): array
    {
        $key = $this->key($name);

        if ($key === '') {
            return $this->result('unresolved', $entityType, null, $key, 'empty_candidate_identity');
        }

        $model = match ($entityType) {
            'campus' => Campus::query(),
            'program' => Program::query(),
            default => null,
        };

        if ($model === null) {
            return $this->result('unsupported', $entityType, null, $key, 'unsupported_entity_type');
        }

        $matches = $model->where('institution_id', $institutionId)
            ->where(function ($query) use ($key, $name) {
                $query->where('slug', Str::slug($name))
                    ->orWhereRaw('LOWER(TRIM(name)) = ?', [Str::lower(trim($name))]);
            })
            ->limit(2)
            ->get(['id', 'name', 'slug']);

        if ($matches->count() > 1) {
            return $this->result('ambiguous', $entityType, null, $key, 'multiple_canonical_matches');
        }

        if ($matches->count() === 1) {
            $match = $matches->first();

            return $this->result('matched', $entityType, (int) $match->id, $key, 'exact_normalized_match');
        }

        return $this->result('unresolved', $entityType, null, $key, 'no_exact_canonical_match');
    }

    public function key(string $value): string
    {
        $value = Str::lower(trim($value));
        $value = preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? '';
        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    private function result(string $state, string $entityType, ?int $canonicalId, string $key, string $reason): array
    {
        return [
            'state' => $state,
            'entity_type' => $entityType,
            'canonical_id' => $canonicalId,
            'normalized_key' => $key,
            'reason' => $reason,
        ];
    }
}

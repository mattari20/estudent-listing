<?php

namespace App\Services;

use App\Models\IdentityMatchCandidate;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\InstitutionName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstituteIdentityResolver
{
    public function resolve(ImportRow $row): int
    {
        return DB::transaction(function () use ($row): int {
            $lockedRow = ImportRow::query()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            $payload = $lockedRow->normalized_payload ?? $lockedRow->payload ?? [];
            $name = trim((string) ($payload['name'] ?? ''));
            $slug = trim((string) ($payload['slug'] ?? ''));

            if ($name === '') {
                return 0;
            }

            $names = collect([$name, $payload['official_name'] ?? '', $payload['short_name'] ?? ''])
                ->map(fn ($value) => Str::lower(trim((string) $value)))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $matches = Institution::query()->where(function ($query) use ($slug, $names): void {
                if ($slug !== '') {
                    $query->where('slug', $slug);
                }

                foreach (['name', 'official_name', 'short_name'] as $field) {
                    $query->orWhereIn(DB::raw('LOWER('.$field.')'), $names);
                }
            })->get();

            $aliasIds = InstitutionName::query()
                ->whereIn(DB::raw('LOWER(name)'), $names)
                ->pluck('institution_id');

            if ($aliasIds->isNotEmpty()) {
                $matches = $matches->merge(Institution::query()->whereIn('id', $aliasIds)->get());
            }

            $matches = $matches->unique('id')->values();

            foreach ($matches as $institution) {
                $type = $slug !== '' && $institution->slug === $slug
                    ? 'exact_slug'
                    : (in_array(Str::lower((string) $institution->official_name), $names, true)
                        ? 'exact_official_name'
                        : (in_array(Str::lower((string) $institution->short_name), $names, true)
                            ? 'exact_short_name'
                            : (InstitutionName::query()
                                ->where('institution_id', $institution->id)
                                ->whereIn(DB::raw('LOWER(name)'), $names)
                                ->exists()
                                ? 'exact_alias'
                                : 'exact_name')));

                $identityKey = hash('sha256', 'identity-match:v1|'.$lockedRow->id.'|'.$institution->id);
                $candidate = IdentityMatchCandidate::query()->firstOrNew([
                    'import_row_id' => $lockedRow->id,
                    'institution_id' => $institution->id,
                ]);
                $candidate->fill([
                    'identity_key' => $identityKey,
                    'match_type' => $type,
                    'confidence' => $type === 'exact_slug' ? 1.0000 : 0.9800,
                    'status' => 'candidate',
                    'reason' => 'Exact normalized match against canonical name, official name, short name, slug, or recorded alias.',
                    'matched_at' => now(),
                ]);
                $candidate->save();
            }

            $lockedRow->update(['match_state' => $matches->isNotEmpty() ? 'matched' : 'new_candidate']);
            app(ImportConflictDetectionService::class)->detect($lockedRow);

            return $matches->count();
        });
    }
}

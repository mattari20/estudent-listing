<?php

namespace App\Services;

use App\Models\ImportRow;
use App\Models\Institution;

class ImportConflictDetectionService
{
    public function detect(ImportRow $row): int
    {
        $row->loadMissing('batch');
        $payload = $row->normalized_payload ?? $row->payload ?? [];
        $count = 0;

        if ($row->batch->entity_type !== 'institution') {
            return 0;
        }

        $slug = strtolower(trim((string) ($payload['slug'] ?? '')));
        if ($slug !== '') {
            $others = $row->batch->rows()->where('id', '!=', $row->id)->get();

            foreach ($others as $other) {
                $otherPayload = $other->normalized_payload ?? $other->payload ?? [];
                if (strtolower(trim((string) ($otherPayload['slug'] ?? ''))) === $slug) {
                    $conflict = app(ImportConflictLifecycleService::class)->open(
                        $row,
                        'duplicate_batch_identity',
                        'high',
                        'Another row in the same batch has the same normalized slug.',
                        null,
                        (int) $other->id,
                        ['slug' => $slug]
                    );
                    $count += $conflict->wasRecentlyCreated ? 1 : 0;
                }
            }
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name !== '') {
            $same = Institution::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->get();
            if ($same->count() > 1) {
                $ids = $same->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
                $conflict = app(ImportConflictLifecycleService::class)->open(
                    $row,
                    'multiple_canonical_name_matches',
                    'critical',
                    'More than one canonical institution has the same normalized name.',
                    Institution::class,
                    null,
                    ['institution_ids' => $ids]
                );
                $count += $conflict->wasRecentlyCreated ? 1 : 0;
            }
        }

        return $count;
    }
}

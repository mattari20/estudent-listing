<?php

namespace App\Services;

use App\Models\ImportRow;
use App\Models\Institution;

class ImportConflictDetectionService
{
    public function __construct(private readonly ImportConflictLifecycleService $lifecycle)
    {
    }

    public function detect(ImportRow $row): int
    {
        $row->loadMissing('batch');
        $payload = $row->normalized_payload ?? $row->payload ?? [];
        $count = 0;

        if ($row->batch->entity_type === 'institution') {
            $slug = trim((string) ($payload['slug'] ?? ''));
            if ($slug !== '') {
                $others = $row->batch->rows()->where('id', '!=', $row->id)->get();

                foreach ($others as $other) {
                    $otherPayload = $other->normalized_payload ?? $other->payload ?? [];
                    if (strtolower(trim((string) ($otherPayload['slug'] ?? ''))) === strtolower($slug)) {
                        $count += $this->lifecycle->open(
                            $row,
                            'duplicate_batch_identity',
                            'high',
                            'Another row in the same batch has the same normalized slug.',
                            null,
                            $other->id,
                            ['slug' => $slug]
                        );
                    }
                }
            }

            $name = trim((string) ($payload['name'] ?? ''));
            if ($name !== '') {
                $same = Institution::query()->whereRaw('LOWER(name)=?', [mb_strtolower($name)])->get();
                if ($same->count() > 1) {
                    $count += $this->lifecycle->open(
                        $row,
                        'multiple_canonical_name_matches',
                        'critical',
                        'More than one canonical institution has the same normalized name.',
                        null,
                        null,
                        ['institution_ids' => $same->pluck('id')->values()->all()]
                    );
                }
            }
        }

        return $count;
    }
}

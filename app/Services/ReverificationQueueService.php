<?php

namespace App\Services;

use App\Models\DataReverificationQueue;
use App\Models\Evidence;

class ReverificationQueueService
{
    private const PRIORITY_RANK = [
        'low' => 10,
        'normal' => 20,
        'high' => 30,
        'urgent' => 40,
        'critical' => 50,
    ];

    public function enqueueEvidence(Evidence $evidence, string $reason = 'stale_evidence', string $priority = 'normal'): DataReverificationQueue
    {
        $identity = [
            'subject_type' => Evidence::class,
            'subject_id' => $evidence->id,
            'attribute' => null,
            'status' => 'open',
        ];

        $item = DataReverificationQueue::query()->firstOrCreate($identity, [
            'reason' => $reason,
            'priority' => $priority,
            'source_id' => $evidence->source_id,
            'evidence_id' => $evidence->id,
            'observed_at' => now(),
        ]);

        if ($item->wasRecentlyCreated) {
            return $item;
        }

        $currentRank = self::PRIORITY_RANK[$item->priority] ?? self::PRIORITY_RANK['normal'];
        $incomingRank = self::PRIORITY_RANK[$priority] ?? self::PRIORITY_RANK['normal'];

        if ($incomingRank > $currentRank) {
            $item->forceFill([
                'priority' => $priority,
                'reason' => $reason,
            ])->save();
        }

        return $item;
    }
}

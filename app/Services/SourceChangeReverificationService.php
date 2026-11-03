<?php

namespace App\Services;

use App\Models\DataReverificationQueue;
use App\Models\SourceChangeAssertionLink;
use Illuminate\Support\Facades\DB;

class SourceChangeReverificationService
{
    public function enqueue(SourceChangeAssertionLink $link): DataReverificationQueue
    {
        $assertion = $link->assertion;
        $identity = [
            $assertion->subject_type,
            (int) $assertion->subject_id,
            $assertion->attribute,
        ];
        $identityKey = hash('sha256', json_encode($identity, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($link, $assertion, $identity, $identityKey): DataReverificationQueue {
            DB::table('data_reverification_queue_identities')->insertOrIgnore([
                'identity_key' => $identityKey,
                'subject_type' => $identity[0],
                'subject_id' => $identity[1],
                'attribute' => $identity[2],
                'active_queue_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $identityRow = DB::table('data_reverification_queue_identities')
                ->where('identity_key', $identityKey)
                ->lockForUpdate()
                ->first();

            $activeQueue = $identityRow?->active_queue_id
                ? DataReverificationQueue::query()
                    ->whereKey($identityRow->active_queue_id)
                    ->lockForUpdate()
                    ->first()
                : null;

            if ($activeQueue !== null && $activeQueue->status === 'open') {
                return $activeQueue;
            }

            $queue = DataReverificationQueue::create([
                'subject_type' => $assertion->subject_type,
                'subject_id' => $assertion->subject_id,
                'attribute' => $assertion->attribute,
                'reason' => 'monitored_source_changed',
                'priority' => $link->impact === 'high' ? 'high' : 'normal',
                'status' => 'open',
                'source_id' => $assertion->evidence?->source_id,
                'evidence_id' => $assertion->evidence_id,
                'observed_at' => now(),
                'notes' => $link->reason,
            ]);

            DB::table('data_reverification_queue_identities')
                ->where('identity_key', $identityKey)
                ->update([
                    'active_queue_id' => $queue->id,
                    'updated_at' => now(),
                ]);

            return $queue;
        }, 3);
    }
}

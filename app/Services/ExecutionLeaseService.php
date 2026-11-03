<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExecutionLeaseService
{
    /**
     * Run one governed pipeline operation under a database-backed lease.
     *
     * A null result means another live owner holds the lease. Database errors
     * deliberately propagate so callers fail closed instead of running unlocked.
     */
    public function run(string $taskKey, Closure $callback, int $ttlMinutes = 120): mixed
    {
        $ownerToken = (string) Str::uuid();
        $runId = (string) Str::uuid();
        $now = now();
        $expiresAt = $now->copy()->addMinutes($ttlMinutes);

        DB::table('governed_execution_leases')->insertOrIgnore([
            'task_key' => $taskKey,
            'owner_token' => $ownerToken,
            'run_id' => $runId,
            'acquired_at' => $now,
            'expires_at' => $expiresAt,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Atomic compare-and-swap: only an absent/expired lease can be claimed.
        DB::table('governed_execution_leases')
            ->where('task_key', $taskKey)
            ->where('expires_at', '<=', $now)
            ->update([
                'owner_token' => $ownerToken,
                'run_id' => $runId,
                'acquired_at' => $now,
                'expires_at' => $expiresAt,
                'updated_at' => $now,
            ]);

        $owned = DB::table('governed_execution_leases')
            ->where('task_key', $taskKey)
            ->where('owner_token', $ownerToken)
            ->exists();

        if (! $owned) {
            return null;
        }

        try {
            return $callback();
        } finally {
            // A stale process must never release a successor's lease.
            DB::table('governed_execution_leases')
                ->where('task_key', $taskKey)
                ->where('owner_token', $ownerToken)
                ->delete();
        }
    }
}

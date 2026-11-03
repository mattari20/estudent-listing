<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ExecutionLeaseService
{
    /**
     * Execute a governed task under one durable lease.
     *
     * Returns null when another live owner holds the task. Database and ledger
     * failures propagate; callers never fall back to unlocked execution.
     *
     * The callback may accept a heartbeat callable and should invoke it during
     * long-running work. A lost lease causes heartbeat to throw before work
     * should continue.
     */
    public function run(string $taskKey, Closure $callback, int $ttlMinutes = 120): mixed
    {
        if ($ttlMinutes < 1) {
            throw new \InvalidArgumentException('Execution lease TTL must be at least one minute.');
        }

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

        $startedAt = now();

        try {
            DB::table('governed_execution_runs')->insert([
                'id' => $runId,
                'task_key' => $taskKey,
                'owner_token' => $ownerToken,
                'runner' => $this->runnerName(),
                'application_revision' => $this->applicationRevision(),
                'status' => 'running',
                'started_at' => $startedAt,
                'heartbeat_at' => $startedAt,
                'created_at' => $startedAt,
                'updated_at' => $startedAt,
            ]);

            $this->recordEvent($runId, 'started');

            $heartbeat = function () use ($taskKey, $ownerToken, $runId, $ttlMinutes): void {
                $now = now();
                DB::table('governed_execution_leases')
                    ->where('task_key', $taskKey)
                    ->where('owner_token', $ownerToken)
                    ->where('expires_at', '>', $now)
                    ->update([
                        'expires_at' => $now->copy()->addMinutes($ttlMinutes),
                        'updated_at' => $now,
                    ]);

                $stillOwned = DB::table('governed_execution_leases')
                    ->where('task_key', $taskKey)
                    ->where('owner_token', $ownerToken)
                    ->where('expires_at', '>', $now)
                    ->exists();

                if (! $stillOwned) {
                    throw new RuntimeException('Governed execution lease ownership was lost.');
                }

                DB::table('governed_execution_runs')
                    ->where('id', $runId)
                    ->where('owner_token', $ownerToken)
                    ->update(['heartbeat_at' => $now, 'updated_at' => $now]);

                $this->recordEvent($runId, 'heartbeat');
            };

            $result = $callback($heartbeat);

            if (is_int($result) && $result !== 0) {
                $failureCode = 'command_exit_code_'.$result;
                DB::table('governed_execution_runs')
                    ->where('id', $runId)
                    ->where('owner_token', $ownerToken)
                    ->update([
                        'status' => 'failed',
                        'finished_at' => now(),
                        'failure_code' => $failureCode,
                        'updated_at' => now(),
                    ]);
                $this->recordEvent($runId, 'failed', $failureCode);

                return $result;
            }

            DB::table('governed_execution_runs')
                ->where('id', $runId)
                ->where('owner_token', $ownerToken)
                ->update(['status' => 'completed', 'finished_at' => now(), 'updated_at' => now()]);
            $this->recordEvent($runId, 'completed');

            return $result;
        } catch (Throwable $exception) {
            // Store only the exception class, never its message or potentially sensitive payload.
            if (DB::table('governed_execution_runs')->where('id', $runId)->exists()) {
                DB::table('governed_execution_runs')
                    ->where('id', $runId)
                    ->where('owner_token', $ownerToken)
                    ->update([
                        'status' => 'failed',
                        'finished_at' => now(),
                        'failure_code' => mb_substr(get_class($exception), 0, 160),
                        'updated_at' => now(),
                    ]);
                $this->recordEvent($runId, 'failed', mb_substr(get_class($exception), 0, 160));
            }

            throw $exception;
        } finally {
            DB::table('governed_execution_leases')
                ->where('task_key', $taskKey)
                ->where('owner_token', $ownerToken)
                ->delete();
        }
    }

    private function recordEvent(string $runId, string $eventType, ?string $failureCode = null): void
    {
        $now = now();

        DB::table('governed_execution_run_events')->insert([
            'run_id' => $runId,
            'event_type' => $eventType,
            'occurred_at' => $now,
            'failure_code' => $failureCode,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function runnerName(): string
    {
        if (getenv('GITHUB_ACTIONS') === 'true') {
            return 'github-actions';
        }

        if (app()->runningInConsole()) {
            return 'artisan';
        }

        return 'application';
    }

    private function applicationRevision(): ?string
    {
        $revision = getenv('GITHUB_SHA') ?: getenv('APP_REVISION');

        return is_string($revision) && preg_match('/^[a-f0-9]{7,40}$/i', $revision)
            ? $revision
            : null;
    }
}

<?php

namespace Tests\Feature;

use App\Services\ExecutionLeaseService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExecutionLeaseServiceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_only_one_live_owner_can_run_a_governed_task(): void
    {
        $lease = app(ExecutionLeaseService::class);
        $nestedResult = 'not-attempted';

        $result = $lease->run('governed-institute-discovery-pipeline', function () use ($lease, &$nestedResult): string {
            $nestedResult = $lease->run('governed-institute-discovery-pipeline', fn () => 'should-not-run');

            return 'owner-ran';
        });

        $this->assertSame('owner-ran', $result);
        $this->assertNull($nestedResult);
        $this->assertDatabaseCount('governed_execution_leases', 0);
        $this->assertDatabaseHas('governed_execution_runs', [
            'task_key' => 'governed-institute-discovery-pipeline',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('governed_execution_run_events', ['event_type' => 'started']);
        $this->assertDatabaseHas('governed_execution_run_events', ['event_type' => 'completed']);
    }


    public function test_separate_database_connection_cannot_claim_a_live_lease(): void
    {
        config(['database.connections.lease_contender' => config('database.connections.'.config('database.default'))]);
        DB::purge('lease_contender');

        $lease = app(ExecutionLeaseService::class);
        $contenderUpdated = null;

        $lease->run('two-connection-contention', function () use (&$contenderUpdated): void {
            $contenderUpdated = DB::connection('lease_contender')
                ->table('governed_execution_leases')
                ->where('task_key', 'two-connection-contention')
                ->where('expires_at', '<=', now())
                ->update([
                    'owner_token' => (string) Str::uuid(),
                    'expires_at' => now()->addMinutes(10),
                ]);
        });

        $this->assertSame(0, $contenderUpdated);
        DB::purge('lease_contender');
    }

    public function test_heartbeat_renews_lease_and_records_an_event(): void
    {
        $lease = app(ExecutionLeaseService::class);
        $called = false;

        $lease->run('heartbeat-test', function ($heartbeat) use (&$called): void {
            $heartbeat();
            $called = true;
        }, 10);

        $this->assertTrue($called);
        $this->assertDatabaseHas('governed_execution_run_events', ['event_type' => 'heartbeat']);
    }

    public function test_expired_lease_can_be_recovered_by_a_new_owner(): void
    {
        $lease = app(ExecutionLeaseService::class);
        $recovered = false;

        $lease->run('governed-institute-discovery-pipeline', function () use ($lease, &$recovered): void {
            DB::table('governed_execution_leases')
                ->where('task_key', 'governed-institute-discovery-pipeline')
                ->update(['expires_at' => now()->subSecond()]);
            $recovered = $lease->run('governed-institute-discovery-pipeline', fn () => true);
        });

        $this->assertTrue($recovered);
        $this->assertDatabaseCount('governed_execution_leases', 0);
    }

    public function test_stale_owner_cannot_delete_a_successor_lease(): void
    {
        $lease = app(ExecutionLeaseService::class);
        $successorToken = (string) Str::uuid();

        $lease->run('governed-institute-discovery-pipeline', function () use ($successorToken): void {
            DB::table('governed_execution_leases')
                ->where('task_key', 'governed-institute-discovery-pipeline')
                ->update([
                    'owner_token' => $successorToken,
                    'run_id' => (string) Str::uuid(),
                ]);
        });

        $this->assertDatabaseHas('governed_execution_leases', [
            'task_key' => 'governed-institute-discovery-pipeline',
            'owner_token' => $successorToken,
        ]);
    }

    public function test_callback_exception_is_recorded_and_releases_its_lease(): void
    {
        $lease = app(ExecutionLeaseService::class);

        try {
            $lease->run('failure-test', function (): void {
                throw new \RuntimeException('expected test failure');
            });
            $this->fail('The callback exception should propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('expected test failure', $exception->getMessage());
        }

        $this->assertDatabaseMissing('governed_execution_leases', ['task_key' => 'failure-test']);
        $this->assertDatabaseHas('governed_execution_runs', [
            'task_key' => 'failure-test',
            'status' => 'failed',
            'failure_code' => \RuntimeException::class,
        ]);
        $this->assertDatabaseHas('governed_execution_run_events', ['event_type' => 'failed']);
    }
}

<?php

namespace Tests\Feature;

use App\Services\ExecutionLeaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExecutionLeaseServiceTest extends TestCase
{
    use RefreshDatabase;

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
    }

    public function test_expired_lease_can_be_recovered_by_a_new_owner(): void
    {
        $lease = app(ExecutionLeaseService::class);
        $recovered = false;

        $lease->run('governed-institute-discovery-pipeline', function () use ($lease, &$recovered): void {
            DB::table('governed_execution_leases')->update(['expires_at' => now()->subSecond()]);
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
            DB::table('governed_execution_leases')->update([
                'owner_token' => $successorToken,
                'run_id' => (string) Str::uuid(),
            ]);
        });

        $this->assertDatabaseHas('governed_execution_leases', [
            'task_key' => 'governed-institute-discovery-pipeline',
            'owner_token' => $successorToken,
        ]);
    }

    public function test_callback_exception_releases_only_its_own_lease(): void
    {
        $lease = app(ExecutionLeaseService::class);

        try {
            $lease->run('governed-institute-discovery-pipeline', function (): void {
                throw new \RuntimeException('expected test failure');
            });
            $this->fail('The callback exception should propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('expected test failure', $exception->getMessage());
        }

        $this->assertDatabaseCount('governed_execution_leases', 0);
    }
}

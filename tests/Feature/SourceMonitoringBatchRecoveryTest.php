<?php

namespace Tests\Feature;

use App\Models\MonitoringAlert;
use App\Models\SecurityAuditEvent;
use App\Models\Source;
use App\Models\SourceMonitoringPolicy;
use App\Services\SourceMonitoringBatchService;
use App\Services\SourceMonitoringPipeline;
use App\Services\MonitoringPolicyCheckpointService;
use App\Services\MonitoringPolicyStateService;
use App\Services\ExecutionLeaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SourceMonitoringBatchRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_pipeline_infrastructure_exception_leaves_attempt_incomplete_for_recovery(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Infrastructure Failure Source',
            'url' => 'https://example.test/infrastructure-failure',
            'status' => 'active',
        ]);

        $policy = SourceMonitoringPolicy::create([
            'name' => 'Infrastructure Failure Policy',
            'fresh_after_days' => 14,
            'stale_after_days' => 30,
            'priority' => 'normal',
            'status' => 'active',
            'source_id' => $source->id,
            'cadence' => 'daily',
            'consecutive_failures' => 2,
        ]);

        $pipeline = Mockery::mock(SourceMonitoringPipeline::class);
        $pipeline->shouldReceive('run')
            ->once()
            ->with(Mockery::type(Source::class))
            ->andThrow(new RuntimeException('sensitive infrastructure detail'));
        $this->app->instance(SourceMonitoringPipeline::class, $pipeline);

        try {
            app(SourceMonitoringBatchService::class)->run($policy->id);
            $this->fail('Infrastructure exceptions must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('sensitive infrastructure detail', $exception->getMessage());
        }

        $policy->refresh();
        $this->assertSame(2, $policy->consecutive_failures);
        $this->assertNull($policy->last_failure_at);
        $this->assertNull($policy->last_run_at);

        $policyRun = DB::table('monitoring_policy_runs')->where('source_monitoring_policy_id', $policy->id)->first();
        $this->assertSame('running', $policyRun->status);
        $sourceCheckpoint = DB::table('monitoring_policy_run_sources')
            ->where('monitoring_policy_run_id', $policyRun->id)
            ->where('source_id', $source->id)
            ->first();
        $this->assertSame('running', $sourceCheckpoint->status);

        $this->assertSame(0, MonitoringAlert::where('alert_type', 'retrieval_failure')->count());
        $this->assertSame(0, SecurityAuditEvent::where('event_type', 'monitoring_retrieval_failure')->count());
    }

    public function test_new_attempt_marks_abandoned_policy_run_interrupted_and_records_completion(): void
    {
        $policy = SourceMonitoringPolicy::create([
            'name' => 'Interrupted Run Policy',
            'fresh_after_days' => 14,
            'stale_after_days' => 30,
            'priority' => 'normal',
            'status' => 'active',
            'cadence' => 'daily',
            'consecutive_failures' => 0,
        ]);

        $abandonedId = DB::table('monitoring_policy_runs')->insertGetId([
            'source_monitoring_policy_id' => $policy->id,
            'execution_run_id' => null,
            'status' => 'running',
            'started_at' => now()->subMinutes(20),
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        $summary = app(SourceMonitoringBatchService::class)->run($policy->id);

        $this->assertSame(1, $summary['policies']);
        $this->assertSame('interrupted', DB::table('monitoring_policy_runs')->where('id', $abandonedId)->value('status'));
        $this->assertSame('superseded_by_new_attempt', DB::table('monitoring_policy_runs')->where('id', $abandonedId)->value('failure_code'));
        $this->assertDatabaseHas('monitoring_policy_runs', [
            'source_monitoring_policy_id' => $policy->id,
            'status' => 'completed',
        ]);
    }


    public function test_policy_validation_exception_is_recorded_without_source_processing(): void
    {
        $policy = SourceMonitoringPolicy::create([
            'name' => 'Invalid Recovery Policy',
            'fresh_after_days' => 14,
            'stale_after_days' => 14,
            'priority' => 'normal',
            'status' => 'active',
            'cadence' => 'daily',
            'consecutive_failures' => 0,
        ]);

        try {
            app(SourceMonitoringBatchService::class)->run($policy->id);
            $this->fail('Invalid policy validation must abort monitoring.');
        } catch (\InvalidArgumentException) {
            $this->assertDatabaseHas('monitoring_policy_runs', [
                'source_monitoring_policy_id' => $policy->id,
                'status' => 'failed',
                'failure_code' => 'policy_validation_failed',
            ]);
        }

        $this->assertDatabaseCount('monitoring_policy_run_sources', 0);
        $policy->refresh();
        $this->assertNull($policy->last_run_at);
    }

    public function test_lost_heartbeat_ownership_aborts_before_source_pipeline(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Lease Loss Source',
            'url' => 'https://example.test/lease-loss',
            'status' => 'active',
        ]);
        $policy = SourceMonitoringPolicy::create([
            'name' => 'Lease Loss Policy',
            'fresh_after_days' => 14,
            'stale_after_days' => 30,
            'priority' => 'normal',
            'status' => 'active',
            'source_id' => $source->id,
            'cadence' => 'daily',
            'consecutive_failures' => 0,
        ]);

        $pipeline = Mockery::mock(SourceMonitoringPipeline::class);
        $pipeline->shouldReceive('run')->once()->andReturn([
            'run' => (object) ['status' => 'completed', 'id' => 455],
            'observation' => null,
            'intelligence' => null,
        ]);
        $this->app->instance(SourceMonitoringPipeline::class, $pipeline);

        try {
            app(ExecutionLeaseService::class)->run(
                'monitoring-policy-heartbeat-loss-test',
                function ($heartbeat, string $executionRunId) use ($policy): int {
                    $heartbeats = 0;
                    $ownedHeartbeat = function () use ($heartbeat, &$heartbeats): void {
                        $heartbeats++;
                        if ($heartbeats === 4) {
                            DB::table('governed_execution_leases')
                                ->where('task_key', 'monitoring-policy-heartbeat-loss-test')
                                ->update(['owner_token' => (string) Str::uuid()]);
                        }

                        $heartbeat();
                    };

                    app(SourceMonitoringBatchService::class)->run($policy->id, $ownedHeartbeat, $executionRunId);

                    return 0;
                }
            );
            $this->fail('Lost execution ownership must abort the batch.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Governed execution lease ownership was lost.', $exception->getMessage());
        }

        $this->assertDatabaseHas('monitoring_policy_runs', [
            'source_monitoring_policy_id' => $policy->id,
            'status' => 'running',
        ]);
        $sourceCheckpoint = DB::table('monitoring_policy_run_sources')->first();
        $this->assertNotNull($sourceCheckpoint);
        $this->assertSame('running', $sourceCheckpoint->status);
        $this->assertNull($policy->fresh()->last_run_at);
        $this->assertDatabaseHas('governed_execution_runs', [
            'task_key' => 'monitoring-policy-heartbeat-loss-test',
            'status' => 'failed',
        ]);
        $this->assertDatabaseHas('governed_execution_leases', [
            'task_key' => 'monitoring-policy-heartbeat-loss-test',
        ]);
    }

    public function test_source_checkpoint_insert_failure_prevents_pipeline_execution(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Checkpoint Insert Failure Source',
            'url' => 'https://example.test/checkpoint-insert',
            'status' => 'active',
        ]);
        $policy = SourceMonitoringPolicy::create([
            'name' => 'Checkpoint Insert Failure Policy',
            'fresh_after_days' => 14,
            'stale_after_days' => 30,
            'priority' => 'normal',
            'status' => 'active',
            'source_id' => $source->id,
            'cadence' => 'daily',
            'consecutive_failures' => 0,
        ]);

        $checkpoints = Mockery::mock(MonitoringPolicyCheckpointService::class)->makePartial();
        $checkpoints->shouldReceive('startSource')->once()->andThrow(new RuntimeException('checkpoint store unavailable'));
        $this->app->instance(MonitoringPolicyCheckpointService::class, $checkpoints);

        $pipeline = Mockery::mock(SourceMonitoringPipeline::class);
        $pipeline->shouldNotReceive('run');
        $this->app->instance(SourceMonitoringPipeline::class, $pipeline);

        try {
            app(SourceMonitoringBatchService::class)->run($policy->id);
            $this->fail('A source checkpoint insertion failure must abort processing.');
        } catch (RuntimeException $exception) {
            $this->assertSame('checkpoint store unavailable', $exception->getMessage());
        }

        $this->assertDatabaseHas('monitoring_policy_runs', [
            'source_monitoring_policy_id' => $policy->id,
            'status' => 'running',
        ]);
        $this->assertDatabaseCount('monitoring_policy_run_sources', 0);
    }

    public function test_source_completion_checkpoint_failure_leaves_source_and_policy_incomplete(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Checkpoint Completion Failure Source',
            'url' => 'https://example.test/checkpoint-completion',
            'status' => 'active',
        ]);
        $policy = SourceMonitoringPolicy::create([
            'name' => 'Checkpoint Completion Failure Policy',
            'fresh_after_days' => 14,
            'stale_after_days' => 30,
            'priority' => 'normal',
            'status' => 'active',
            'source_id' => $source->id,
            'cadence' => 'daily',
            'consecutive_failures' => 0,
        ]);

        $pipeline = Mockery::mock(SourceMonitoringPipeline::class);
        $pipeline->shouldReceive('run')->once()->andReturn([
            'run' => (object) ['status' => 'completed', 'id' => 456],
            'observation' => null,
            'intelligence' => null,
        ]);
        $this->app->instance(SourceMonitoringPipeline::class, $pipeline);

        $checkpoints = Mockery::mock(MonitoringPolicyCheckpointService::class)->makePartial();
        $checkpoints->shouldReceive('completeSource')->once()->andThrow(new RuntimeException('completion checkpoint unavailable'));
        $this->app->instance(MonitoringPolicyCheckpointService::class, $checkpoints);

        try {
            app(SourceMonitoringBatchService::class)->run($policy->id);
            $this->fail('A source completion checkpoint failure must abort policy finalization.');
        } catch (RuntimeException $exception) {
            $this->assertSame('completion checkpoint unavailable', $exception->getMessage());
        }

        $policyRun = DB::table('monitoring_policy_runs')->where('source_monitoring_policy_id', $policy->id)->first();
        $this->assertSame('running', $policyRun->status);
        $sourceCheckpoint = DB::table('monitoring_policy_run_sources')->where('monitoring_policy_run_id', $policyRun->id)->first();
        $this->assertSame('running', $sourceCheckpoint->status);
        $this->assertNull($policy->fresh()->last_run_at);
    }

    public function test_policy_state_failure_rolls_back_last_run_and_policy_finalization(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Policy State Failure Source',
            'url' => 'https://example.test/policy-state-failure',
            'status' => 'active',
        ]);
        $policy = SourceMonitoringPolicy::create([
            'name' => 'Policy State Failure Policy',
            'fresh_after_days' => 14,
            'stale_after_days' => 30,
            'priority' => 'normal',
            'status' => 'active',
            'source_id' => $source->id,
            'cadence' => 'daily',
            'consecutive_failures' => 0,
        ]);

        $pipeline = Mockery::mock(SourceMonitoringPipeline::class);
        $pipeline->shouldReceive('run')->once()->andReturn([
            'run' => (object) ['status' => 'completed', 'id' => null],
            'observation' => null,
            'intelligence' => null,
        ]);
        $this->app->instance(SourceMonitoringPipeline::class, $pipeline);

        $state = Mockery::mock(MonitoringPolicyStateService::class);
        $state->shouldReceive('success')->once()->andThrow(new RuntimeException('policy state storage unavailable'));
        $this->app->instance(MonitoringPolicyStateService::class, $state);

        try {
            app(SourceMonitoringBatchService::class)->run($policy->id);
            $this->fail('Policy-state persistence failure must abort finalization.');
        } catch (RuntimeException $exception) {
            $this->assertSame('policy state storage unavailable', $exception->getMessage());
        }

        $policyRun = DB::table('monitoring_policy_runs')->where('source_monitoring_policy_id', $policy->id)->first();
        $this->assertSame('running', $policyRun->status);
        $this->assertNull($policy->fresh()->last_run_at);
        $this->assertNull($policy->fresh()->last_success_at);
        $this->assertDatabaseHas('monitoring_policy_run_sources', [
            'monitoring_policy_run_id' => $policyRun->id,
            'source_id' => $source->id,
            'status' => 'completed',
        ]);
    }


    public function test_last_run_at_write_failure_rolls_back_policy_state_and_finalization(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Last Run Failure Source',
            'url' => 'https://example.test/last-run-failure',
            'status' => 'active',
        ]);
        $policy = SourceMonitoringPolicy::create([
            'name' => 'Last Run Failure Policy',
            'fresh_after_days' => 14,
            'stale_after_days' => 30,
            'priority' => 'normal',
            'status' => 'active',
            'source_id' => $source->id,
            'cadence' => 'daily',
            'consecutive_failures' => 0,
        ]);

        $pipeline = Mockery::mock(SourceMonitoringPipeline::class);
        $pipeline->shouldReceive('run')->once()->andReturn([
            'run' => (object) ['status' => 'completed', 'id' => null],
            'observation' => null,
            'intelligence' => null,
        ]);
        $this->app->instance(SourceMonitoringPipeline::class, $pipeline);

        DB::connection()->beforeExecuting(function (string $query): void {
            if (str_contains(strtolower($query), 'last_run_at')) {
                throw new RuntimeException('last run persistence unavailable');
            }
        });

        try {
            app(SourceMonitoringBatchService::class)->run($policy->id);
            $this->fail('A last_run_at persistence failure must abort finalization.');
        } catch (RuntimeException $exception) {
            $this->assertSame('last run persistence unavailable', $exception->getMessage());
        }

        $policy = $policy->fresh();
        $policyRun = DB::table('monitoring_policy_runs')->where('source_monitoring_policy_id', $policy->id)->first();
        $this->assertSame('running', $policyRun->status);
        $this->assertNull($policy->last_run_at);
        $this->assertNull($policy->last_success_at);
    }

    public function test_policy_run_finalization_write_failure_rolls_back_policy_state(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Finalization Failure Source',
            'url' => 'https://example.test/finalization-failure',
            'status' => 'active',
        ]);
        $policy = SourceMonitoringPolicy::create([
            'name' => 'Finalization Failure Policy',
            'fresh_after_days' => 14,
            'stale_after_days' => 30,
            'priority' => 'normal',
            'status' => 'active',
            'source_id' => $source->id,
            'cadence' => 'daily',
            'consecutive_failures' => 0,
        ]);

        $pipeline = Mockery::mock(SourceMonitoringPipeline::class);
        $pipeline->shouldReceive('run')->once()->andReturn([
            'run' => (object) ['status' => 'completed', 'id' => null],
            'observation' => null,
            'intelligence' => null,
        ]);
        $this->app->instance(SourceMonitoringPipeline::class, $pipeline);

        DB::connection()->beforeExecuting(function (string $query): void {
            $sql = strtolower($query);
            if (str_contains($sql, 'update') && str_contains($sql, 'monitoring_policy_runs') && str_contains($sql, 'summary')) {
                throw new RuntimeException('policy finalization unavailable');
            }
        });

        try {
            app(SourceMonitoringBatchService::class)->run($policy->id);
            $this->fail('A policy finalization persistence failure must abort.');
        } catch (RuntimeException $exception) {
            $this->assertSame('policy finalization unavailable', $exception->getMessage());
        }

        $policy = $policy->fresh();
        $policyRun = DB::table('monitoring_policy_runs')->where('source_monitoring_policy_id', $policy->id)->first();
        $this->assertSame('running', $policyRun->status);
        $this->assertNull($policy->last_run_at);
        $this->assertNull($policy->last_success_at);
    }


    public function test_policy_run_checkpoint_insert_failure_rolls_back_interruption_and_prevents_source_work(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Policy Run Insert Failure Source',
            'url' => 'https://example.test/policy-run-insert',
            'status' => 'active',
        ]);
        $policy = SourceMonitoringPolicy::create([
            'name' => 'Policy Run Insert Failure Policy',
            'fresh_after_days' => 14,
            'stale_after_days' => 30,
            'priority' => 'normal',
            'status' => 'active',
            'source_id' => $source->id,
            'cadence' => 'daily',
            'consecutive_failures' => 0,
        ]);
        $previousRunId = DB::table('monitoring_policy_runs')->insertGetId([
            'source_monitoring_policy_id' => $policy->id,
            'execution_run_id' => null,
            'status' => 'running',
            'started_at' => now()->subMinutes(20),
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        DB::connection()->beforeExecuting(function (string $query): void {
            $sql = strtolower($query);
            if (str_contains($sql, 'insert into') && str_contains($sql, 'monitoring_policy_runs')) {
                throw new RuntimeException('policy checkpoint insert failed');
            }
        });
        $pipeline = Mockery::mock(SourceMonitoringPipeline::class);
        $pipeline->shouldNotReceive('run');
        $this->app->instance(SourceMonitoringPipeline::class, $pipeline);

        try {
            app(SourceMonitoringBatchService::class)->run($policy->id);
            $this->fail('Policy-run insertion failure must abort before source processing.');
        } catch (RuntimeException $exception) {
            $this->assertSame('policy checkpoint insert failed', $exception->getMessage());
        }

        $this->assertSame('running', DB::table('monitoring_policy_runs')->where('id', $previousRunId)->value('status'));
        $this->assertDatabaseCount('monitoring_policy_runs', 1);
        $this->assertDatabaseCount('monitoring_policy_run_sources', 0);
    }

    public function test_source_failure_checkpoint_write_failure_aborts_policy_finalization(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Source Failure Checkpoint Source',
            'url' => 'https://example.test/source-failure-checkpoint',
            'status' => 'active',
        ]);
        $policy = SourceMonitoringPolicy::create([
            'name' => 'Source Failure Checkpoint Policy',
            'fresh_after_days' => 14,
            'stale_after_days' => 30,
            'priority' => 'normal',
            'status' => 'active',
            'source_id' => $source->id,
            'cadence' => 'daily',
            'consecutive_failures' => 0,
        ]);

        $pipeline = Mockery::mock(SourceMonitoringPipeline::class);
        $pipeline->shouldReceive('run')->once()->andReturn([
            'run' => (object) ['status' => 'failed', 'id' => 903],
            'observation' => null,
            'intelligence' => null,
        ]);
        $this->app->instance(SourceMonitoringPipeline::class, $pipeline);
        $checkpoints = Mockery::mock(MonitoringPolicyCheckpointService::class)->makePartial();
        $checkpoints->shouldReceive('failSource')->once()->andThrow(new RuntimeException('source failure checkpoint unavailable'));
        $this->app->instance(MonitoringPolicyCheckpointService::class, $checkpoints);

        try {
            app(SourceMonitoringBatchService::class)->run($policy->id);
            $this->fail('Source failure checkpoint failure must abort.');
        } catch (RuntimeException $exception) {
            $this->assertSame('source failure checkpoint unavailable', $exception->getMessage());
        }

        $policyRun = DB::table('monitoring_policy_runs')->where('source_monitoring_policy_id', $policy->id)->first();
        $this->assertSame('running', $policyRun->status);
        $sourceCheckpoint = DB::table('monitoring_policy_run_sources')->where('monitoring_policy_run_id', $policyRun->id)->first();
        $this->assertSame('running', $sourceCheckpoint->status);
        $this->assertNull($policy->fresh()->last_run_at);
    }

    public function test_policy_failure_state_exception_rolls_back_failure_count_and_finalization(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Policy Failure State Source',
            'url' => 'https://example.test/policy-failure-state',
            'status' => 'active',
        ]);
        $policy = SourceMonitoringPolicy::create([
            'name' => 'Policy Failure State Policy',
            'fresh_after_days' => 14,
            'stale_after_days' => 30,
            'priority' => 'normal',
            'status' => 'active',
            'source_id' => $source->id,
            'cadence' => 'daily',
            'consecutive_failures' => 4,
        ]);

        $pipeline = Mockery::mock(SourceMonitoringPipeline::class);
        $pipeline->shouldReceive('run')->once()->andReturn([
            'run' => (object) ['status' => 'failed', 'id' => null],
            'observation' => null,
            'intelligence' => null,
        ]);
        $this->app->instance(SourceMonitoringPipeline::class, $pipeline);
        $state = Mockery::mock(MonitoringPolicyStateService::class);
        $state->shouldReceive('failure')->once()->andThrow(new RuntimeException('failure state write unavailable'));
        $this->app->instance(MonitoringPolicyStateService::class, $state);

        try {
            app(SourceMonitoringBatchService::class)->run($policy->id);
            $this->fail('Policy failure-state persistence exception must abort finalization.');
        } catch (RuntimeException $exception) {
            $this->assertSame('failure state write unavailable', $exception->getMessage());
        }

        $policy = $policy->fresh();
        $policyRun = DB::table('monitoring_policy_runs')->where('source_monitoring_policy_id', $policy->id)->first();
        $this->assertSame('running', $policyRun->status);
        $this->assertSame(4, $policy->consecutive_failures);
        $this->assertNull($policy->last_failure_at);
        $this->assertNull($policy->last_run_at);
    }


    public function test_direct_checkpoint_caller_rejects_a_fresh_running_policy_attempt(): void
    {
        $policy = SourceMonitoringPolicy::create([
            'name' => 'Direct Active Policy',
            'fresh_after_days' => 14,
            'stale_after_days' => 30,
            'priority' => 'normal',
            'status' => 'active',
            'cadence' => 'daily',
            'consecutive_failures' => 0,
        ]);

        $service = app(MonitoringPolicyCheckpointService::class);
        $firstRunId = $service->startPolicyRun($policy->id, null);

        try {
            $service->startPolicyRun($policy->id, null);
            $this->fail('A direct caller must not supersede a fresh running policy attempt.');
        } catch (RuntimeException $exception) {
            $this->assertSame('A monitoring policy run is already active.', $exception->getMessage());
        }

        $this->assertDatabaseCount('monitoring_policy_runs', 1);
        $this->assertDatabaseHas('monitoring_policy_runs', [
            'id' => $firstRunId,
            'source_monitoring_policy_id' => $policy->id,
            'status' => 'running',
        ]);
    }

    public function test_policy_run_heartbeat_refreshes_the_direct_caller_staleness_window(): void
    {
        \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::parse('2026-11-04 11:00:00'));
        try {
            $policy = SourceMonitoringPolicy::create([
                'name' => 'Heartbeat Policy',
                'fresh_after_days' => 14,
                'stale_after_days' => 30,
                'priority' => 'normal',
                'status' => 'active',
                'cadence' => 'daily',
                'consecutive_failures' => 0,
            ]);

            $service = app(MonitoringPolicyCheckpointService::class);
            $runId = $service->startPolicyRun($policy->id, null);
            $startedHeartbeat = DB::table('monitoring_policy_runs')->where('id', $runId)->value('heartbeat_at');

            \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::parse('2026-11-04 11:03:00'));
            $service->heartbeatPolicyRun($runId);

            $this->assertNotSame($startedHeartbeat, DB::table('monitoring_policy_runs')->where('id', $runId)->value('heartbeat_at'));
        } finally {
            \Illuminate\Support\Carbon::setTestNow();
        }
    }


}

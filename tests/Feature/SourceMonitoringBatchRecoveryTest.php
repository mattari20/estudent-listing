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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            'started_at' => now()->subMinutes(10),
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
        } catch (\\InvalidArgumentException) {
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
        $pipeline->shouldNotReceive('run');
        $this->app->instance(SourceMonitoringPipeline::class, $pipeline);
        $heartbeats = 0;

        try {
            app(SourceMonitoringBatchService::class)->run($policy->id, function () use (&$heartbeats): void {
                $heartbeats++;
                if ($heartbeats === 3) {
                    throw new RuntimeException('lease ownership lost');
                }
            });
            $this->fail('Lost ownership must abort the batch.');
        } catch (RuntimeException $exception) {
            $this->assertSame('lease ownership lost', $exception->getMessage());
        }

        $this->assertDatabaseHas('monitoring_policy_runs', [
            'source_monitoring_policy_id' => $policy->id,
            'status' => 'running',
        ]);
        $this->assertDatabaseCount('monitoring_policy_run_sources', 0);
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
            'run' => (object) ['status' => 'completed', 'id' => 789],
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
            'run' => (object) ['status' => 'completed', 'id' => 901],
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
            'run' => (object) ['status' => 'completed', 'id' => 902],
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

}

<?php

namespace Tests\Feature;

use App\Models\MonitoringAlert;
use App\Models\SecurityAuditEvent;
use App\Models\Source;
use App\Models\SourceMonitoringPolicy;
use App\Services\SourceMonitoringBatchService;
use App\Services\SourceMonitoringPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SourceMonitoringBatchRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_thrown_pipeline_exception_marks_policy_failed_and_records_safe_diagnostics(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Recovery Test Source',
            'url' => 'https://example.test/recovery',
            'status' => 'active',
        ]);

        $policy = SourceMonitoringPolicy::create([
            'name' => 'Recovery Test Policy',
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
            ->andThrow(new RuntimeException('sensitive response body must not be logged'));
        $this->app->instance(SourceMonitoringPipeline::class, $pipeline);

        $summary = app(SourceMonitoringBatchService::class)->run($policy->id);

        $policy->refresh();

        $this->assertSame(1, $summary['policies']);
        $this->assertSame(1, $summary['sources']);
        $this->assertSame(1, $summary['failed']);
        $this->assertSame(0, $summary['completed']);
        $this->assertSame(3, $policy->consecutive_failures);
        $this->assertNotNull($policy->last_failure_at);
        $this->assertNotNull($policy->last_run_at);
        $this->assertNotNull($policy->next_run_at);

        $policyRun = DB::table('monitoring_policy_runs')->where('source_monitoring_policy_id', $policy->id)->first();
        $this->assertSame('failed', $policyRun->status);
        $sourceCheckpoint = DB::table('monitoring_policy_run_sources')->where('monitoring_policy_run_id', $policyRun->id)->where('source_id', $source->id)->first();
        $this->assertSame('failed', $sourceCheckpoint->status);
        $this->assertSame(RuntimeException::class, $sourceCheckpoint->failure_code);

        $this->assertSame(1, MonitoringAlert::where('alert_type', 'retrieval_failure')->count());
        $alert = MonitoringAlert::where('alert_type', 'retrieval_failure')->firstOrFail();
        $this->assertStringContainsString(RuntimeException::class, $alert->message);
        $this->assertStringNotContainsString('sensitive response body', $alert->message);

        $audit = SecurityAuditEvent::where('event_type', 'monitoring_retrieval_failure')->firstOrFail();
        $this->assertStringContainsString(RuntimeException::class, $audit->description);
        $this->assertStringNotContainsString('sensitive response body', $audit->description);
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

}

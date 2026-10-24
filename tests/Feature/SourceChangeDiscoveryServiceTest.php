<?php

namespace Tests\Feature;

use App\Models\InstituteDiscoveryRunOutcome;
use App\Models\Source;
use App\Models\SourceChangeObservation;
use App\Models\SourceSnapshot;
use App\Models\SourceMonitoringPolicy;
use App\Services\SourceChangeDiscoveryService;
use App\Services\SourceMonitoringBatchService;
use App\Services\SourceMonitoringPipeline;
use App\Services\MonitoringPolicyValidationService;
use App\Services\MonitoringPolicyStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourceChangeDiscoveryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unchanged_observation_records_no_change_without_running_agent(): void
    {
        $source = $this->createSource();
        $snapshot = $this->createSnapshot($source, 'unchanged snapshot');
        $observation = SourceChangeObservation::create([
            'source_id' => $source->id,
            'current_snapshot_id' => $snapshot->id,
            'observed_at' => now(),
            'change_state' => 'unchanged',
            'materiality' => 'none',
        ]);

        $result = app(SourceChangeDiscoveryService::class)->process($observation);

        $this->assertSame('no_change', $result['outcome']);
        $this->assertDatabaseHas('institute_discovery_run_outcomes', [
            'id' => $result['outcome_id'],
            'source_id' => $source->id,
            'outcome' => 'no_change',
        ]);
    }

    public function test_changed_observation_without_usable_snapshot_records_empty_snapshot(): void
    {
        $source = $this->createSource();
        $snapshot = $this->createSnapshot($source, '');
        $observation = SourceChangeObservation::create([
            'source_id' => $source->id,
            'current_snapshot_id' => $snapshot->id,
            'observed_at' => now(),
            'change_state' => 'changed',
            'materiality' => 'high',
        ]);

        $result = app(SourceChangeDiscoveryService::class)->process($observation);

        $this->assertSame('empty_snapshot', $result['outcome']);
        $this->assertDatabaseHas('institute_discovery_run_outcomes', [
            'id' => $result['outcome_id'],
            'source_id' => $source->id,
            'outcome' => 'empty_snapshot',
        ]);
    }

    public function test_monitoring_batch_records_outcome_for_unchanged_source_observation(): void
    {
        $source = $this->createSource();
        $snapshot = $this->createSnapshot($source, 'unchanged source content');
        $observation = SourceChangeObservation::create([
            'source_id' => $source->id,
            'current_snapshot_id' => $snapshot->id,
            'observed_at' => now(),
            'change_state' => 'unchanged',
            'materiality' => 'none',
        ]);

        $policy = SourceMonitoringPolicy::create([
            'name' => 'Phase 2 unchanged outcome policy',
            'subject_type' => 'program',
            'attribute' => 'automated_discovery',
            'fresh_after_days' => 7,
            'stale_after_days' => 30,
            'priority' => 'normal',
            'status' => 'active',
            'source_id' => $source->id,
            'cadence' => 'hourly',
            'next_run_at' => now()->subMinute(),
        ]);

        $pipeline = \Mockery::mock(SourceMonitoringPipeline::class);
        $pipeline->shouldReceive('run')->once()->with(\Mockery::type(Source::class))->andReturn([
            'run' => (object) ['status' => 'completed'],
            'observation' => $observation,
            'intelligence' => ['queued' => 0],
        ]);
        $this->app->instance(SourceMonitoringPipeline::class, $pipeline);

        $validation = \Mockery::mock(MonitoringPolicyValidationService::class);
        $validation->shouldReceive('validate')->once()->with(\Mockery::type(SourceMonitoringPolicy::class));
        $this->app->instance(MonitoringPolicyValidationService::class, $validation);

        $policyState = \Mockery::mock(MonitoringPolicyStateService::class);
        $policyState->shouldReceive('success')->once()->with(\Mockery::type(SourceMonitoringPolicy::class));
        $this->app->instance(MonitoringPolicyStateService::class, $policyState);

        $summary = app(SourceMonitoringBatchService::class)->run($policy->id);

        $this->assertSame(1, $summary['outcomes_recorded']);
        $this->assertDatabaseHas('institute_discovery_run_outcomes', [
            'source_id' => $source->id,
            'source_change_observation_id' => $observation->id,
            'outcome' => 'no_change',
        ]);
    }


    public function test_changed_source_with_only_duplicates_records_duplicate_counts_not_empty_candidates(): void
    {
        $url = 'https://example.edu/programs';
        config()->set('institute_agent.profiles.duplicate-test', [
            'source' => ['url' => $url],
            'monitoring' => ['auto_discovery' => true, 'use_ai' => false],
        ]);
        $source = $this->createSource();
        $source->update(['url' => $url]);
        $snapshot = $this->createSnapshot($source, '<html>unchanged programme list</html>');
        $observation = SourceChangeObservation::create([
            'source_id' => $source->id,
            'current_snapshot_id' => $snapshot->id,
            'observed_at' => now(),
            'change_state' => 'changed',
            'materiality' => 'high',
        ]);

        $agent = \\Mockery::mock(InstituteDiscoveryAgentService::class);
        $agent->shouldReceive('runFromSnapshot')->once()->with('duplicate-test', $snapshot, false)->andReturn([
            'total_rows' => 63,
            'candidate_rows' => 0,
            'duplicate_rows' => 63,
            'batch_id' => 17,
            'review_package' => ['status' => 'created'],
        ]);
        $this->app->instance(InstituteDiscoveryAgentService::class, $agent);

        $result = app(SourceChangeDiscoveryService::class)->process($observation);

        $this->assertSame('duplicates_only', $result['outcome']);
        $this->assertDatabaseHas('institute_discovery_run_outcomes', [
            'id' => $result['outcome_id'],
            'outcome' => 'duplicates_only',
            'candidate_count' => 0,
        ]);
        $stored = \\App\\Models\\InstituteDiscoveryRunOutcome::query()->findOrFail($result['outcome_id']);
        $this->assertSame(63, $stored->details['extracted_rows']);
        $this->assertSame(63, $stored->details['duplicate_rows']);
    }

    private function createSource(): Source
    {
        return Source::create([
            'source_type' => 'official',
            'name' => 'Test source',
            'url' => 'https://example.edu/programs',
            'authority_scope' => 'official programmes',
            'status' => 'active',
        ]);
    }

    private function createSnapshot(Source $source, string $content): SourceSnapshot
    {
        return SourceSnapshot::create([
            'source_id' => $source->id,
            'reference' => $source->url,
            'content_hash' => hash('sha256', $content),
            'content_length' => strlen($content),
            'content_type' => 'text/html',
            'content' => $content,
            'captured_at' => now(),
            'status' => 'captured',
        ]);
    }
}

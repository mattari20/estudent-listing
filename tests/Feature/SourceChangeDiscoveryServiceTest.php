<?php

namespace Tests\Feature;

use App\Models\InstituteDiscoveryRunOutcome;
use App\Models\Source;
use App\Models\SourceChangeObservation;
use App\Services\SourceChangeDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourceChangeDiscoveryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unchanged_observation_records_no_change_without_running_agent(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Test source',
            'url' => 'https://example.edu/programs',
            'authority_scope' => 'official programmes',
            'status' => 'active',
        ]);
        $observation = SourceChangeObservation::create([
            'source_id' => $source->id,
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
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Test source',
            'url' => 'https://example.edu/programs',
            'authority_scope' => 'official programmes',
            'status' => 'active',
        ]);
        $observation = SourceChangeObservation::create([
            'source_id' => $source->id,
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
}

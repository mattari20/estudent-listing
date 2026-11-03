<?php

namespace Tests\Feature;

use App\Models\InstituteDiscoveryRunOutcome;
use App\Models\Source;
use App\Models\SourceChangeObservation;
use App\Models\SourceSnapshot;
use App\Services\SourceChangeDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourceChangeDiscoveryIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_change_outcome_is_reused_for_repeated_processing_of_same_observation(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Outcome Idempotency Source',
            'url' => 'https://example.test/official',
            'status' => 'active',
        ]);
        $snapshot = SourceSnapshot::create([
            'source_id' => $source->id,
            'reference' => $source->url,
            'content_hash' => hash('sha256', 'same'),
            'content_length' => 4,
            'content' => 'same',
            'captured_at' => now(),
            'status' => 'captured',
        ]);
        $observation = SourceChangeObservation::create([
            'source_id' => $source->id,
            'current_snapshot_id' => $snapshot->id,
            'change_state' => 'unchanged',
            'materiality' => 'none',
            'status' => 'detected',
            'summary' => 'No content change.',
            'observed_at' => now(),
        ]);

        $service = app(SourceChangeDiscoveryService::class);
        $first = $service->process($observation);
        $second = $service->process($observation);

        $this->assertSame($first['outcome_id'], $second['outcome_id']);
        $this->assertSame('no_change', $second['outcome']);
        $this->assertDatabaseCount('institute_discovery_run_outcomes', 1);
    }

    public function test_no_matching_profile_outcome_is_reused_for_same_changed_observation(): void
    {
        config()->set('institute_agent.profiles', []);

        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Unmatched Outcome Source',
            'url' => 'https://example.test/unmatched',
            'status' => 'active',
        ]);
        $snapshot = SourceSnapshot::create([
            'source_id' => $source->id,
            'reference' => $source->url,
            'content_hash' => hash('sha256', 'changed'),
            'content_length' => 7,
            'content' => 'changed',
            'captured_at' => now(),
            'status' => 'captured',
        ]);
        $observation = SourceChangeObservation::create([
            'source_id' => $source->id,
            'current_snapshot_id' => $snapshot->id,
            'change_state' => 'changed',
            'materiality' => 'unknown',
            'status' => 'detected',
            'summary' => 'Content changed.',
            'observed_at' => now(),
        ]);

        $service = app(SourceChangeDiscoveryService::class);
        $first = $service->process($observation);
        $second = $service->process($observation);

        $this->assertSame($first['outcome_id'], $second['outcome_id']);
        $this->assertSame('no_matching_profile', $second['outcome']);
        $this->assertDatabaseCount('institute_discovery_run_outcomes', 1);
    }

    public function test_database_rejects_duplicate_discovery_outcome_idempotency_keys(): void
    {
        $outcome = InstituteDiscoveryRunOutcome::create([
            'idempotency_key' => str_repeat('a', 64),
            'outcome' => 'no_matching_profile',
            'candidate_count' => 0,
            'occurred_at' => now(),
        ]);

        $this->assertNotNull($outcome->id);
        $this->expectException(\Illuminate\Database\QueryException::class);
        InstituteDiscoveryRunOutcome::create([
            'idempotency_key' => str_repeat('a', 64),
            'outcome' => 'no_matching_profile',
            'candidate_count' => 0,
            'occurred_at' => now(),
        ]);
    }
}

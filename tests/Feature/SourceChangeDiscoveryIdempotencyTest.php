<?php

namespace Tests\Feature;

use App\Models\InstituteDiscoveryRunOutcome;
use App\Models\Institution;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Source;
use App\Models\SourceChangeObservation;
use App\Models\SourceSnapshot;
use App\Services\SourceChangeDiscoveryService;
use App\Services\OfficialSourceIdentityVerifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    public function test_outcome_insert_failure_leaves_committed_batch_replayable_without_duplicate_candidates(): void
    {
        $observation = $this->prepareCandidateDiscoveryObservation();
        $triggered = false;

        DB::connection()->beforeExecuting(function (string $query) use (&$triggered): void {
            $sql = strtolower($query);
            if (! $triggered && str_contains($sql, 'insert into') && str_contains($sql, 'institute_discovery_run_outcomes')) {
                $triggered = true;
                throw new \\RuntimeException('injected discovery outcome persistence failure');
            }
        });

        try {
            app(SourceChangeDiscoveryService::class)->process($observation);
            $this->fail('The discovery outcome persistence failure must propagate.');
        } catch (\\RuntimeException $exception) {
            $this->assertSame('injected discovery outcome persistence failure', $exception->getMessage());
        }

        $this->assertTrue($triggered);
        $this->assertDatabaseCount('institute_discovery_run_outcomes', 0);
        $this->assertDatabaseCount('import_batches', 1);
        $this->assertDatabaseCount('import_rows', 2);

        $replayed = app(SourceChangeDiscoveryService::class)->process($observation);

        $this->assertSame('candidates_created', $replayed['run_outcome']['outcome']);
        $this->assertDatabaseCount('institute_discovery_run_outcomes', 1);
        $this->assertDatabaseCount('import_batches', 1);
        $this->assertDatabaseCount('import_rows', 2);
        $this->assertDatabaseCount('evidence', 1);
        $this->assertDatabaseCount('assertions', 2);
    }

    public function test_partial_candidate_failure_records_failed_outcome_then_recovers_on_replay(): void
    {
        $observation = $this->prepareCandidateDiscoveryObservation();
        $insertedRows = 0;
        $triggered = false;

        DB::connection()->beforeExecuting(function (string $query) use (&$insertedRows, &$triggered): void {
            $sql = strtolower($query);
            if (! str_contains($sql, 'insert into') || ! str_contains($sql, 'import_rows')) {
                return;
            }

            $insertedRows++;
            if (! $triggered && $insertedRows === 2) {
                $triggered = true;
                throw new \\RuntimeException('injected partial candidate row failure');
            }
        });

        try {
            app(SourceChangeDiscoveryService::class)->process($observation);
            $this->fail('The candidate row failure must propagate after recording a failed outcome.');
        } catch (\\RuntimeException $exception) {
            $this->assertSame('injected partial candidate row failure', $exception->getMessage());
        }

        $this->assertTrue($triggered);
        $failedOutcome = InstituteDiscoveryRunOutcome::query()->firstOrFail();
        $this->assertSame('failed', $failedOutcome->outcome);
        $this->assertDatabaseCount('import_batches', 0);
        $this->assertDatabaseCount('import_rows', 0);
        $this->assertDatabaseCount('evidence', 0);
        $this->assertDatabaseCount('assertions', 0);

        $replayed = app(SourceChangeDiscoveryService::class)->process($observation);

        $this->assertSame($failedOutcome->id, $replayed['run_outcome']['outcome_id']);
        $this->assertSame('candidates_created', $replayed['run_outcome']['outcome']);
        $this->assertDatabaseCount('institute_discovery_run_outcomes', 1);
        $this->assertDatabaseCount('import_batches', 1);
        $this->assertDatabaseCount('import_rows', 2);
        $this->assertDatabaseCount('evidence', 1);
        $this->assertDatabaseCount('assertions', 2);
    }

    private function prepareCandidateDiscoveryObservation(): SourceChangeObservation
    {
        Storage::fake('local');

        $url = 'https://example.test/discovery-profile';
        $profile = [
            'entity_type' => 'program',
            'institution_slug' => 'phase1c-candidate-recovery-institution',
            'source' => [
                'url' => $url,
                'type' => 'official',
                'name' => 'Phase 1C Candidate Recovery Source',
            ],
            'monitoring' => [
                'use_ai' => false,
                'auto_discovery' => true,
            ],
        ];
        config()->set('institute_agent.profiles', ['phase1c-candidate-recovery' => $profile]);

        Institution::create([
            'name' => 'Phase 1C Candidate Recovery Institution',
            'slug' => 'phase1c-candidate-recovery-institution',
            'status' => 'active',
        ]);
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Phase 1C Candidate Recovery Source',
            'url' => $url,
            'status' => 'active',
        ]);
        $html = '<html><body>
            <a>Bachelor of Science (BS) in Computer Science</a>
            <a>Bachelor of Science (BS) in Psychology</a>
        </body></html>';
        $snapshot = SourceSnapshot::create([
            'source_id' => $source->id,
            'reference' => $url,
            'content_hash' => hash('sha256', $html),
            'content_length' => strlen($html),
            'content' => $html,
            'captured_at' => now(),
            'status' => 'captured',
        ]);
        $observation = SourceChangeObservation::create([
            'source_id' => $source->id,
            'current_snapshot_id' => $snapshot->id,
            'change_state' => 'changed',
            'materiality' => 'unknown',
            'status' => 'detected',
            'summary' => 'Discovery candidate recovery fixture.',
            'observed_at' => now(),
        ]);

        $verifier = \\Mockery::mock(OfficialSourceIdentityVerifier::class);
        $verifier->shouldReceive('verify')->twice()->andReturn([
            'verified' => true,
            'blockers' => [],
        ]);
        $this->app->instance(OfficialSourceIdentityVerifier::class, $verifier);

        return $observation;
    }

}

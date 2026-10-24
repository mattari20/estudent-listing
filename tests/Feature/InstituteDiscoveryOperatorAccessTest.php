<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\InstituteDiscoveryRunOutcome;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstituteDiscoveryOperatorAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_discovery_run_history(): void
    {
        $response = $this->get('/institutes/operations/discovery-run-history');

        $this->assertContains($response->getStatusCode(), [302, 401]);
    }

    public function test_guest_cannot_download_discovery_review_packages(): void
    {
        $response = $this->get('/institutes/operations/discovery-packages/1/csv');

        $this->assertContains($response->getStatusCode(), [302, 401, 404]);
    }

    public function test_package_retention_dry_run_does_not_delete_old_private_files(): void
    {
        Storage::fake('local');
        $directory = 'institute-discovery-review-packages/snapshot-991';
        Storage::disk('local')->put($directory.'/manifest.json', '{"snapshot_id":991}');
        Storage::disk('local')->put($directory.'/candidates.csv', "candidate_name\nExample\n");
        touch(Storage::disk('local')->path($directory.'/manifest.json'), now()->subDays(120)->timestamp);

        $this->artisan('institutes:prune-discovery-review-packages', ['--days' => 90])
            ->assertExitCode(0);

        Storage::disk('local')->assertExists($directory.'/manifest.json');
        Storage::disk('local')->assertExists($directory.'/candidates.csv');
    }

    public function test_authenticated_operator_can_download_private_csv_and_manifest_with_safe_headers(): void
    {
        Storage::fake('local');
        $operator = User::create([
            'name' => 'Phase Two Operator',
            'email' => 'phase-two-operator@example.test',
            'password' => 'test-password',
            'role' => 'operator',
        ]);
        $batch = ImportBatch::create([
            'batch_key' => 'phase-two-download-'.uniqid(),
            'entity_type' => 'program',
            'source_type' => 'automated_discovery',
            'status' => 'validated',
            'total_rows' => 1,
            'valid_rows' => 1,
            'invalid_rows' => 0,
            'candidate_rows' => 1,
            'received_at' => now(),
        ]);
        $batch->rows()->create([
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'unresolved',
            'payload' => ['name' => 'Example Program', '_provenance' => ['snapshot_id' => 991]],
            'normalized_payload' => ['name' => 'Example Program'],
        ]);
        $directory = 'institute-discovery-review-packages/snapshot-991';
        Storage::disk('local')->put($directory.'/candidates.csv', "candidate_name\nExample Program\n");
        Storage::disk('local')->put($directory.'/manifest.json', json_encode(['snapshot_id' => 991]));

        $this->actingAs($operator)
            ->get('/institutes/operations/discovery-packages/'.$batch->id.'/csv')
            ->assertOk()
            ->assertHeader('Cache-Control', 'private, no-store, max-age=0')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($operator)
            ->get('/institutes/operations/discovery-packages/'.$batch->id.'/manifest')
            ->assertOk()
            ->assertHeader('Cache-Control', 'private, no-store, max-age=0')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_operator_can_view_paginated_discovery_run_history(): void
    {
        $operator = User::create([
            'name' => 'Phase Two History Operator',
            'email' => 'phase-two-history@example.test',
            'password' => 'test-password',
            'role' => 'operator',
        ]);

        foreach (range(1, 51) as $index) {
            InstituteDiscoveryRunOutcome::create([
                'profile_key' => 'test-profile',
                'outcome' => 'no_change',
                'candidate_count' => 0,
                'occurred_at' => now()->subMinutes($index),
                'details' => ['test' => true],
            ]);
        }

        $this->actingAs($operator)
            ->get('/institutes/operations/discovery-run-history?page=2')
            ->assertOk()
            ->assertViewHas('outcomes', function ($outcomes): bool {
                return $outcomes->currentPage() === 2
                    && $outcomes->perPage() === 50
                    && $outcomes->count() === 1
                    && $outcomes->total() === 51;
            });
    }

    public function test_operator_download_returns_not_found_for_non_discovery_batch_or_missing_package(): void
    {
        $operator = User::create([
            'name' => 'Phase Two Missing Package Operator',
            'email' => 'phase-two-missing@example.test',
            'password' => 'test-password',
            'role' => 'operator',
        ]);
        $manualBatch = ImportBatch::create([
            'batch_key' => 'phase-two-manual-'.uniqid(),
            'entity_type' => 'program',
            'source_type' => 'manual',
            'status' => 'validated',
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'candidate_rows' => 0,
            'received_at' => now(),
        ]);

        $this->actingAs($operator)
            ->get('/institutes/operations/discovery-packages/'.$manualBatch->id.'/csv')
            ->assertNotFound();

        $discoveryBatch = ImportBatch::create([
            'batch_key' => 'phase-two-missing-file-'.uniqid(),
            'entity_type' => 'program',
            'source_type' => 'automated_discovery',
            'status' => 'validated',
            'total_rows' => 1,
            'valid_rows' => 1,
            'invalid_rows' => 0,
            'candidate_rows' => 1,
            'received_at' => now(),
        ]);
        $discoveryBatch->rows()->create([
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'unresolved',
            'payload' => ['name' => 'Example Program', '_provenance' => ['snapshot_id' => 992]],
            'normalized_payload' => ['name' => 'Example Program'],
        ]);

        Storage::fake('local');
        $this->actingAs($operator)
            ->get('/institutes/operations/discovery-packages/'.$discoveryBatch->id.'/csv')
            ->assertNotFound();
    }

}

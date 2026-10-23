<?php

namespace Tests\Feature;

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
}

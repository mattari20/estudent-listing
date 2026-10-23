<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstituteDiscoveryPackageRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_retention_command_defaults_to_dry_run_and_preserves_recent_package(): void
    {
        Storage::fake('local');
        $directory = 'institute-discovery-review-packages/snapshot-123';
        Storage::disk('local')->put($directory.'/manifest.json', json_encode(['created_at' => now()->toIso8601String()]));
        Storage::disk('local')->put($directory.'/candidates.csv', "candidate_name\nExample\n");

        $this->artisan('institutes:prune-discovery-review-packages')
            ->expectsOutputToContain('DRY RUN')
            ->assertExitCode(0);

        Storage::disk('local')->assertExists($directory.'/manifest.json');
        Storage::disk('local')->assertExists($directory.'/candidates.csv');
    }

    public function test_retention_command_rejects_invalid_retention_days(): void
    {
        $this->artisan('institutes:prune-discovery-review-packages', ['--days' => '0'])
            ->expectsOutputToContain('positive integer')
            ->assertExitCode(1);

        $this->artisan('institutes:prune-discovery-review-packages', ['--days' => 'not-a-number'])
            ->expectsOutputToContain('positive integer')
            ->assertExitCode(1);
    }

    public function test_retention_command_handles_missing_package_root(): void
    {
        Storage::fake('local');

        $this->artisan('institutes:prune-discovery-review-packages')
            ->expectsOutputToContain('nothing to prune')
            ->assertExitCode(0);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstituteDiscoveryPackageRetentionSafetyTest extends TestCase
{
    public function test_old_package_is_only_deleted_when_apply_is_explicit(): void
    {
        Storage::fake('local');
        $directory = 'institute-discovery-review-packages/snapshot-987';
        Storage::disk('local')->put($directory.'/manifest.json', '{"test":true}');
        Storage::disk('local')->put($directory.'/candidates.csv', "candidate_name\nOld Candidate\n");
        $old = now()->subDays(120)->timestamp;
        touch(Storage::disk('local')->path($directory.'/manifest.json'), $old);

        $this->artisan('institutes:prune-discovery-review-packages', ['--days' => '90'])
            ->expectsOutputToContain('DRY RUN')
            ->assertExitCode(0);
        Storage::disk('local')->assertExists($directory.'/manifest.json');

        $this->artisan('institutes:prune-discovery-review-packages', ['--days' => '90', '--apply' => true])
            ->expectsOutputToContain('APPLY')
            ->assertExitCode(0);
        Storage::disk('local')->assertMissing($directory.'/manifest.json');
        Storage::disk('local')->assertMissing($directory.'/candidates.csv');
    }

    public function test_retention_skips_incomplete_or_unrecognized_directories(): void
    {
        Storage::fake('local');
        $root = 'institute-discovery-review-packages';
        Storage::disk('local')->put($root.'/snapshot-42/manifest.json', '{"test":true}');
        Storage::disk('local')->put($root.'/snapshot-0/manifest.json', '{"test":true}');
        Storage::disk('local')->put($root.'/unexpected/manifest.json', '{"test":true}');

        $this->artisan('institutes:prune-discovery-review-packages', ['--days' => '1', '--apply' => true])
            ->assertExitCode(0);

        Storage::disk('local')->assertExists($root.'/snapshot-42/manifest.json');
        Storage::disk('local')->assertExists($root.'/snapshot-0/manifest.json');
        Storage::disk('local')->assertExists($root.'/unexpected/manifest.json');
    }
}

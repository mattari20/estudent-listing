<?php

namespace Tests\Feature;

use App\Models\Evidence;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Source;
use App\Models\SourceSnapshot;
use App\Services\InstituteDiscoveryReviewPackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class InstituteDiscoveryReviewPackageRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private function batch(string $key = 'review-package-recovery'): array
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Recovery test source',
            'url' => 'https://example.edu/programs',
            'authority_scope' => 'official programme listing',
            'status' => 'active',
        ]);
        $snapshot = SourceSnapshot::create([
            'source_id' => $source->id,
            'reference' => $source->url,
            'content_hash' => hash('sha256', 'recovery snapshot'),
            'content_length' => 17,
            'content_type' => 'text/html',
            'content' => 'recovery snapshot',
            'captured_at' => now(),
            'status' => 'captured',
            'metadata' => [],
        ]);
        $batch = ImportBatch::create([
            'batch_key' => $key,
            'entity_type' => 'institution',
            'source_type' => 'automated_discovery',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'candidate_rows' => 0,
            'received_at' => now(),
            'completed_at' => now(),
            'notes' => 'No candidates extracted from source snapshot #'.$snapshot->id.'.',
        ]);

        return [$source, $snapshot, $batch];
    }

    private function paths(ImportBatch $batch, SourceSnapshot $snapshot): array
    {
        $directory = 'institute-discovery-review-packages/batch-'.$batch->id.'-snapshot-'.$snapshot->id;

        return [$directory, $directory.'/candidates.csv', $directory.'/manifest.json'];
    }

    private function old(string $absolute): void
    {
        touch($absolute, now()->subDays(10)->timestamp);
        if (is_dir($absolute)) {
            touch($absolute, now()->subDays(10)->timestamp);
        } else {
            touch(dirname($absolute), now()->subDays(10)->timestamp);
        }
    }

    public function test_interrupted_csv_only_package_is_rebuilt_and_manifest_is_written_last(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        [, $csvPath] = $this->paths($batch, $snapshot);
        Storage::disk('local')->put($csvPath, "partial,csv\n");

        $result = app(InstituteDiscoveryReviewPackageService::class)->create($batch->fresh());
        $this->assertSame('created', $result['status']);
        $this->assertFileExists(Storage::disk('local')->path($result['csv_path']));
        $manifest = json_decode(Storage::disk('local')->get($result['manifest_path']), true);
        $this->assertSame($batch->id, $manifest['batch_id']);
        $this->assertSame($snapshot->id, $manifest['snapshot_id']);
    }

    public function test_missing_csv_is_rebuilt(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $result = app(InstituteDiscoveryReviewPackageService::class)->create($batch->fresh());
        Storage::disk('local')->delete($result['csv_path']);
        $again = app(InstituteDiscoveryReviewPackageService::class)->create($batch->fresh());
        $this->assertSame('created', $again['status']);
        Storage::disk('local')->assertExists($again['csv_path']);
    }

    public function test_missing_manifest_is_rebuilt_and_pair_is_not_reported_ready(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $service = app(InstituteDiscoveryReviewPackageService::class);
        $result = $service->create($batch->fresh());
        Storage::disk('local')->delete($result['manifest_path']);
        $again = $service->create($batch->fresh());
        $this->assertSame('created', $again['status']);
        $this->assertSame('reused', $service->create($batch->fresh())['status']);
    }

    public function test_invalid_checksum_is_rebuilt(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $service = app(InstituteDiscoveryReviewPackageService::class);
        $result = $service->create($batch->fresh());
        $manifest = json_decode(Storage::disk('local')->get($result['manifest_path']), true);
        $manifest['checksums']['candidates_csv_sha256'] = str_repeat('0', 64);
        Storage::disk('local')->put($result['manifest_path'], json_encode($manifest));
        $this->assertSame('created', $service->create($batch->fresh())['status']);
        $this->assertSame('reused', $service->create($batch->fresh())['status']);
    }

    public function test_invalid_manifest_package_type_or_identity_is_rebuilt(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $service = app(InstituteDiscoveryReviewPackageService::class);
        $result = $service->create($batch->fresh());
        $manifest = json_decode(Storage::disk('local')->get($result['manifest_path']), true);
        $manifest['package_type'] = 'wrong_package';
        $manifest['snapshot_id'] = $snapshot->id + 100;
        Storage::disk('local')->put($result['manifest_path'], json_encode($manifest));
        $this->assertSame('created', $service->create($batch->fresh())['status']);
        $this->assertSame('reused', $service->create($batch->fresh())['status']);
    }

    public function test_incomplete_database_batch_cannot_receive_or_report_a_ready_package(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $batch->update(['status' => 'processing', 'completed_at' => null]);
        $this->expectException(RuntimeException::class);
        app(InstituteDiscoveryReviewPackageService::class)->create($batch->fresh());
    }

    public function test_orphan_package_is_reported_in_dry_run_with_exact_paths(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $orphanId = 987654;
        $directory = 'institute-discovery-review-packages/batch-'.$orphanId.'-snapshot-'.$snapshot->id;
        Storage::disk('local')->put($directory.'/candidates.csv', "header\n");
        Storage::disk('local')->put($directory.'/manifest.json', '{}');
        $this->old(Storage::disk('local')->path($directory.'/candidates.csv'));
        $this->old(Storage::disk('local')->path($directory.'/manifest.json'));
        $this->old(Storage::disk('local')->path($directory));

        $exit = Artisan::call('institutes:prune-discovery-review-packages', ['--min-age-hours' => 168]);
        $this->assertSame(0, $exit);
        $this->assertStringContainsString($directory.'/candidates.csv', Artisan::output());
        Storage::disk('local')->assertExists($directory.'/candidates.csv');
    }

    public function test_stale_temporary_artifact_is_detected_without_deleting_valid_package(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $service = app(InstituteDiscoveryReviewPackageService::class);
        $result = $service->create($batch->fresh());
        $temp = $result['csv_path'];
        $temp = dirname($temp).'/.candidates.csv.tmp-0123456789abcdef';
        Storage::disk('local')->put($temp, 'interrupted');
        $this->old(Storage::disk('local')->path($temp));

        Artisan::call('institutes:prune-discovery-review-packages', ['--min-age-hours' => 168]);
        $this->assertStringContainsString($temp, Artisan::output());
        Storage::disk('local')->assertExists($result['csv_path']);
        Storage::disk('local')->assertExists($result['manifest_path']);
    }

    public function test_valid_package_is_preserved_even_when_old_and_apply_is_requested(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $result = app(InstituteDiscoveryReviewPackageService::class)->create($batch->fresh());
        $this->old(Storage::disk('local')->path($result['csv_path']));
        $this->old(Storage::disk('local')->path($result['manifest_path']));
        $this->old(Storage::disk('local')->path(dirname($result['csv_path'])));

        $exit = Artisan::call('institutes:prune-discovery-review-packages', [
            '--min-age-hours' => 168,
            '--apply' => true,
        ]);
        $this->assertSame(0, $exit);
        Storage::disk('local')->assertExists($result['csv_path']);
        Storage::disk('local')->assertExists($result['manifest_path']);
    }

    public function test_recent_orphan_artifacts_are_preserved(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $directory = 'institute-discovery-review-packages/batch-987655-snapshot-'.$snapshot->id;
        Storage::disk('local')->put($directory.'/candidates.csv', "header\n");
        Storage::disk('local')->put($directory.'/manifest.json', '{}');

        Artisan::call('institutes:prune-discovery-review-packages', [
            '--min-age-hours' => 168,
            '--apply' => true,
        ]);
        Storage::disk('local')->assertExists($directory.'/candidates.csv');
    }

    public function test_explicit_apply_removes_only_old_orphan_package_artifacts(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $directory = 'institute-discovery-review-packages/batch-987656-snapshot-'.$snapshot->id;
        Storage::disk('local')->put($directory.'/candidates.csv', "header\n");
        Storage::disk('local')->put($directory.'/manifest.json', '{}');
        $this->old(Storage::disk('local')->path($directory.'/candidates.csv'));
        $this->old(Storage::disk('local')->path($directory.'/manifest.json'));
        $this->old(Storage::disk('local')->path($directory));

        $exit = Artisan::call('institutes:prune-discovery-review-packages', [
            '--min-age-hours' => 168,
            '--apply' => true,
        ]);
        $this->assertSame(0, $exit);
        $this->assertDirectoryDoesNotExist(Storage::disk('local')->path($directory));
    }

    public function test_unexpected_files_make_orphan_directory_ambiguous_and_non_deletable(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $directory = 'institute-discovery-review-packages/batch-987657-snapshot-'.$snapshot->id;
        Storage::disk('local')->put($directory.'/candidates.csv', "header\n");
        Storage::disk('local')->put($directory.'/unknown.bin', 'unknown');
        $this->old(Storage::disk('local')->path($directory.'/candidates.csv'));
        $this->old(Storage::disk('local')->path($directory.'/unknown.bin'));
        $this->old(Storage::disk('local')->path($directory));

        Artisan::call('institutes:prune-discovery-review-packages', [
            '--min-age-hours' => 168,
            '--apply' => true,
        ]);
        $this->assertDirectoryExists(Storage::disk('local')->path($directory));
        Storage::disk('local')->assertExists($directory.'/unknown.bin');
    }

    public function test_symlinked_package_directory_fails_closed(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $disk = Storage::disk('local');
        $root = $disk->path('institute-discovery-review-packages');
        if (! is_dir($root)) {
            mkdir($root, 0770, true);
        }
        $outside = dirname($root).'/outside-review-package';
        mkdir($outside, 0770, true);
        $link = $root.'/batch-987658-snapshot-'.$snapshot->id;
        if (! function_exists('symlink') || ! @symlink($outside, $link)) {
            $this->markTestSkipped('Symlink creation is unavailable in this test environment.');
        }
        $this->expectException(RuntimeException::class);
        app(InstituteDiscoveryReviewPackageService::class)->reconcileOrphans(168, true);
    }


    public function test_path_traversal_is_rejected_by_the_package_path_guard(): void
    {
        Storage::fake('local');
        $service = app(InstituteDiscoveryReviewPackageService::class);
        $method = new \ReflectionMethod($service, 'safeAbsolutePath');

        try {
            $method->invoke($service, Storage::disk('local'), 'institute-discovery-review-packages/../outside', false);
            $this->fail('A traversal path must be rejected.');
        } catch (\ReflectionException $exception) {
            throw $exception;
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Unsafe review-package path', $exception->getMessage());
        }
    }

    public function test_snapshot_from_another_source_is_rejected(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $other = Source::create([
            'source_type' => 'official',
            'name' => 'Other source',
            'url' => 'https://other.example.edu',
            'authority_scope' => 'official',
            'status' => 'active',
        ]);
        $otherSnapshot = SourceSnapshot::create([
            'source_id' => $other->id,
            'reference' => $other->url,
            'content_hash' => hash('sha256', 'other'),
            'content_length' => 5,
            'content_type' => 'text/html',
            'content' => 'other',
            'captured_at' => now(),
            'status' => 'captured',
            'metadata' => [],
        ]);
        $this->expectException(RuntimeException::class);
        app(InstituteDiscoveryReviewPackageService::class)->create($batch->fresh(), $otherSnapshot);
    }

    public function test_repeated_generation_reuses_one_complete_pair_and_keeps_batch_rows_unchanged(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $service = app(InstituteDiscoveryReviewPackageService::class);
        $first = $service->create($batch->fresh());
        $second = $service->create($batch->fresh());
        $this->assertSame('created', $first['status']);
        $this->assertSame('reused', $second['status']);
        $this->assertSame($first['csv_path'], $second['csv_path']);
        $this->assertSame(0, $batch->rows()->count());
        $this->assertSame(0, ImportBatch::query()->whereKey($batch->id)->firstOrFail()->rows()->count());
        $this->assertFileExists(Storage::disk('local')->path('institute-discovery-review-packages/.locks/'.hash('sha256', $batch->id.'|'.$snapshot->id).'.lock'));
    }

    public function test_changed_database_rows_invalidate_a_previously_valid_package(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $row = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'unresolved',
            'payload' => ['name' => 'Original candidate', 'slug' => 'original-candidate'],
        ]);

        $service = app(InstituteDiscoveryReviewPackageService::class);
        $first = $service->create($batch->fresh());
        $firstManifest = json_decode(Storage::disk('local')->get($first['manifest_path']), true);

        $row->update([
            'payload' => ['name' => 'Changed candidate', 'slug' => 'changed-candidate'],
            'notes' => 'reviewer-visible change',
        ]);

        $second = $service->create($batch->fresh());
        $secondManifest = json_decode(Storage::disk('local')->get($second['manifest_path']), true);

        $this->assertSame('created', $second['status']);
        $this->assertNotSame($firstManifest['row_set_sha256'], $secondManifest['row_set_sha256']);
        $this->assertStringContainsString('Changed candidate', Storage::disk('local')->get($second['csv_path']));
        $this->assertSame($row->id, (int) str_getcsv(explode("\n", trim(Storage::disk('local')->get($second['csv_path'])))[1])[1]);
    }

    public function test_changed_evidence_invalidates_a_previously_valid_package(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $evidence = Evidence::create([
            'source_id' => $source->id,
            'snapshot_id' => $snapshot->id,
            'reference' => $source->url,
            'excerpt' => 'Original excerpt',
            'content_hash' => hash('sha256', 'original evidence'),
            'observed_at' => now(),
            'status' => 'active',
        ]);

        $service = app(InstituteDiscoveryReviewPackageService::class);
        $first = $service->create($batch->fresh(), $snapshot, $evidence->id);
        $firstManifest = json_decode(Storage::disk('local')->get($first['manifest_path']), true);

        $evidence->update(['content_hash' => hash('sha256', 'changed evidence')]);

        $second = $service->create($batch->fresh(), $snapshot, $evidence->id);
        $secondManifest = json_decode(Storage::disk('local')->get($second['manifest_path']), true);

        $this->assertSame('created', $second['status']);
        $this->assertNotSame($firstManifest['evidence_set_sha256'], $secondManifest['evidence_set_sha256']);
        $this->assertSame([$evidence->id], $secondManifest['evidence_ids']);
    }

    public function test_evidence_from_another_snapshot_cannot_be_attached_to_a_review_package(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $otherSnapshot = SourceSnapshot::create([
            'source_id' => $source->id,
            'reference' => $source->url,
            'content_hash' => hash('sha256', 'other snapshot'),
            'content_length' => 14,
            'content_type' => 'text/html',
            'content' => 'other snapshot',
            'captured_at' => now()->addMinute(),
            'status' => 'captured',
            'metadata' => [],
        ]);
        $evidence = Evidence::create([
            'source_id' => $source->id,
            'snapshot_id' => $otherSnapshot->id,
            'reference' => $source->url,
            'content_hash' => hash('sha256', 'other evidence'),
            'observed_at' => now(),
            'status' => 'active',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Review-package evidence does not belong to the batch source and snapshot.');
        app(InstituteDiscoveryReviewPackageService::class)->create($batch->fresh(), $snapshot, $evidence->id);
    }

    public function test_legacy_manifest_schema_is_not_reused_as_a_current_package(): void
    {
        Storage::fake('local');
        [$source, $snapshot, $batch] = $this->batch();
        $service = app(InstituteDiscoveryReviewPackageService::class);
        $first = $service->create($batch->fresh());
        $manifest = json_decode(Storage::disk('local')->get($first['manifest_path']), true);
        $manifest['schema_version'] = 2;
        Storage::disk('local')->put($first['manifest_path'], json_encode($manifest));

        $rebuilt = $service->create($batch->fresh());

        $this->assertSame('created', $rebuilt['status']);
        $this->assertSame(3, json_decode(Storage::disk('local')->get($rebuilt['manifest_path']), true)['schema_version']);
    }

}

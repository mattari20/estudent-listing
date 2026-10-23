<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\Institution;
use App\Models\Source;
use App\Models\SourceSnapshot;
use App\Models\SourceMonitoringPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InstituteAgentBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_registers_source_policy_and_performs_initial_discovery_once(): void
    {
        Institution::create([
            'name' => 'Government College University Lahore',
            'slug' => 'government-college-university-lahore',
        ]);

        Http::fake([
            'https://gcu.edu.pk/admissions.php?pg=bs-program' => Http::response(
                '<html><body><a href="/x">Bachelor of Computer Science</a><a href="/y">Bachelor of Artificial Intelligence</a></body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $this->artisan('institutes:bootstrap-agent')
            ->assertExitCode(0);

        $source = Source::query()->where('url', 'https://gcu.edu.pk/admissions.php?pg=bs-program')->first();

        $this->assertNotNull($source);
        $this->assertDatabaseHas('source_monitoring_policies', [
            'source_id' => $source->id,
            'attribute' => 'automated_discovery',
            'status' => 'active',
        ]);
        $this->assertSame(1, SourceSnapshot::query()->where('source_id', $source->id)->count());
        $this->assertSame(1, ImportBatch::query()->where('source_id', $source->id)->count());

        $this->artisan('institutes:bootstrap-agent')
            ->assertExitCode(0);

        $this->assertSame(1, SourceSnapshot::query()->where('source_id', $source->id)->count());
        $this->assertSame(1, ImportBatch::query()->where('source_id', $source->id)->count());
    }

    public function test_bootstrap_retries_an_obsolete_empty_batch_after_extractor_version_changes(): void
    {
        Institution::create(['name' => 'Government College University Lahore', 'slug' => 'government-college-university-lahore']);
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Government College University Lahore — Official BS Programs',
            'url' => 'https://gcu.edu.pk/admissions.php?pg=bs-program',
            'authority_scope' => 'official undergraduate admissions/program listing',
            'status' => 'active',
        ]);
        ImportBatch::create([
            'batch_key' => 'legacy-empty-gcu-discovery',
            'entity_type' => 'program',
            'source_type' => 'automated_discovery',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 0, 'valid_rows' => 0, 'invalid_rows' => 0,
            'candidate_rows' => 0, 'duplicate_rows' => 0,
            'received_at' => now()->subDay(), 'completed_at' => now()->subDay(),
            'notes' => 'Legacy empty batch without extractor version.',
        ]);
        Http::fake([
            'https://gcu.edu.pk/admissions.php?pg=bs-program' => Http::response(
                '<html><body><main><section><h2>Academic Programs</h2><a href="/biology">Biology</a><a href="/biotechnology">Biotechnology</a></section></main></body></html>',
                200, ['Content-Type' => 'text/html']
            ),
        ]);
        $this->artisan('institutes:bootstrap-agent', ['--profile' => ['gcu-lahore-programs']])->assertExitCode(0);
        $latest = ImportBatch::query()->where('source_id', $source->id)->latest('id')->firstOrFail();
        $this->assertSame(2, (int) $latest->total_rows);
        $this->assertStringContainsString('discovery_extractor_version=3', $latest->notes);
    }

    public function test_bootstrap_does_not_repeat_a_current_version_empty_discovery(): void
    {
        Institution::create(['name' => 'Government College University Lahore', 'slug' => 'government-college-university-lahore']);
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Government College University Lahore — Official BS Programs',
            'url' => 'https://gcu.edu.pk/admissions.php?pg=bs-program',
            'authority_scope' => 'official undergraduate admissions/program listing',
            'status' => 'active',
        ]);
        ImportBatch::create([
            'batch_key' => 'current-empty-gcu-discovery',
            'entity_type' => 'program',
            'source_type' => 'automated_discovery',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 0, 'valid_rows' => 0, 'invalid_rows' => 0,
            'candidate_rows' => 0, 'duplicate_rows' => 0,
            'received_at' => now(), 'completed_at' => now(),
            'notes' => 'No candidates extracted. discovery_extractor_version=3',
        ]);
        $this->artisan('institutes:bootstrap-agent', ['--profile' => ['gcu-lahore-programs']])->assertExitCode(0);
        $this->assertSame(1, ImportBatch::query()->where('source_id', $source->id)->count());
        $this->assertSame(0, SourceSnapshot::query()->where('source_id', $source->id)->count());
    }

}

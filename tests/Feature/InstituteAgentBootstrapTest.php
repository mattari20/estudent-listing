<?php

namespace Tests\Feature;

use App\Models\Assertion;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Source;
use App\Models\SourceSnapshot;
use App\Models\SourceMonitoringPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
            'https://www.hec.gov.pk/english/universities/Pages/Punjab/Government-College-University%2C-Lahore.aspx' => Http::response(
                '<html><head><title>Government College University, Lahore — HEC</title></head><body>'
                .'<h1>Government College University, Lahore</h1><p>Sector: Public</p><p>Recognised University: Yes</p><p>City: Lahore</p>'
                .'</body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
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
        $this->assertStringContainsString('discovery_extractor_version=5', $latest->notes);
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
            'notes' => 'No candidates extracted. discovery_extractor_version=5',
        ]);
        $this->artisan('institutes:bootstrap-agent', ['--profile' => ['gcu-lahore-programs']])->assertExitCode(0);
        $this->assertSame(1, ImportBatch::query()->where('source_id', $source->id)->count());
        $this->assertSame(0, SourceSnapshot::query()->where('source_id', $source->id)->count());
    }


    public function test_bootstrap_stages_official_institution_identity_for_review_without_creating_canonical_data(): void
    {
        Source::create([
            'source_type' => 'official',
            'name' => 'Government College University Lahore — Unreachable Former Source',
            'url' => 'https://gcu.edu.pk/',
            'authority_scope' => 'legacy official homepage',
            'status' => 'active',
        ]);
        $oldSource = Source::query()->where('url', 'https://gcu.edu.pk/')->firstOrFail();
        SourceMonitoringPolicy::create([
            'source_id' => $oldSource->id,
            'name' => 'Automated discovery — gcu-lahore-institution-identity',
            'subject_type' => 'institution',
            'attribute' => 'automated_discovery',
            'fresh_after_days' => 7,
            'stale_after_days' => 30,
            'priority' => 'normal',
            'status' => 'active',
            'cadence' => 'weekly',
        ]);

        Storage::fake('local');
        Http::fake([
            'https://www.hec.gov.pk/english/universities/Pages/Punjab/Government-College-University%2C-Lahore.aspx' => Http::response(
                '<html><head><title>Government College University, Lahore — HEC</title></head><body>'
                .'<main><h1>Government College University, Lahore</h1><p>Sector: Public</p>'
                .'<p>Recognised University: Yes</p><p>City: Lahore</p><p>Province: Punjab</p></main>'
                .'</body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $this->artisan('institutes:bootstrap-agent', [
            '--profile' => ['gcu-lahore-institution-identity'],
        ])->assertExitCode(0);

        $this->assertSame('disabled', SourceMonitoringPolicy::query()
            ->where('source_id', $oldSource->id)
            ->where('name', 'Automated discovery — gcu-lahore-institution-identity')
            ->value('status'));

        $source = Source::query()->where('url', 'https://www.hec.gov.pk/english/universities/Pages/Punjab/Government-College-University%2C-Lahore.aspx')->firstOrFail();
        $batch = ImportBatch::query()->where('source_id', $source->id)->firstOrFail();
        $row = ImportRow::query()->where('import_batch_id', $batch->id)->firstOrFail();

        $this->assertSame('institution', $batch->entity_type);
        $this->assertSame('validated', $batch->status);
        $this->assertSame('candidate', $row->status);
        $this->assertSame('new_candidate', $row->match_state);
        $this->assertNull($row->parent_subject_id);
        $this->assertNull($row->parent_subject_type);
        $this->assertSame('Government College University Lahore', $row->normalized_payload['name']);
        $this->assertSame('government-college-university-lahore', $row->normalized_payload['slug']);
        $this->assertSame(0, Institution::query()->count(), 'Discovery must not write a canonical institution.');
        $this->assertDatabaseHas('source_monitoring_policies', [
            'source_id' => $source->id,
            'attribute' => 'automated_discovery',
            'subject_type' => 'institution',
            'status' => 'active',
        ]);
        $assertion = Assertion::query()
            ->where('subject_type', ImportRow::class)
            ->where('subject_id', $row->id)
            ->where('attribute', 'institution_identity_candidate')
            ->firstOrFail();
        $this->assertSame('discovered', $assertion->verification_state);
        $this->assertSame(
            (int) $row->normalized_payload['_provenance']['evidence_id'],
            (int) $assertion->evidence_id
        );
        $this->assertDatabaseHas('evidence', [
            'id' => $assertion->evidence_id,
            'source_id' => $source->id,
            'status' => 'active',
        ]);
        $this->assertSame(1, $batch->fresh()->candidate_rows);
        $this->assertStringContainsString('institution_identity_extractor_version=1', $batch->notes);
        $manifestPath = 'institute-discovery-review-packages/batch-'.$batch->id.'-snapshot-'.$row->normalized_payload['_provenance']['snapshot_id'].'/manifest.json';
        Storage::disk('local')->assertExists($manifestPath);
        $manifest = json_decode(Storage::disk('local')->get($manifestPath), true);
        $this->assertSame(false, $manifest['canonical_mutations_performed']);
        $this->assertSame(false, $manifest['automatic_approval_performed']);
    }

    public function test_bootstrap_disables_a_stale_policy_when_its_required_parent_is_missing(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Government College University Lahore — Official BS Programs',
            'url' => 'https://gcu.edu.pk/admissions.php?pg=bs-program',
            'authority_scope' => 'official undergraduate admissions/program listing',
            'status' => 'active',
        ]);
        SourceMonitoringPolicy::create([
            'source_id' => $source->id,
            'name' => 'Automated discovery — gcu-lahore-programs',
            'subject_type' => 'program',
            'attribute' => 'automated_discovery',
            'fresh_after_days' => 7,
            'stale_after_days' => 30,
            'priority' => 'normal',
            'status' => 'active',
            'cadence' => 'weekly',
        ]);

        $this->artisan('institutes:bootstrap-agent', [
            '--profile' => ['gcu-lahore-programs'],
        ])->assertExitCode(0);

        $this->assertSame('disabled', SourceMonitoringPolicy::query()
            ->where('source_id', $source->id)
            ->where('name', 'Automated discovery — gcu-lahore-programs')
            ->value('status'));
        $this->assertSame(0, SourceSnapshot::query()->where('source_id', $source->id)->count());
        $this->assertSame(0, ImportBatch::query()->where('source_id', $source->id)->count());
    }

}

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
}

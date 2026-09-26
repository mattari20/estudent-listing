<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Services\InstituteDiscoveryAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InstituteDiscoveryAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_discovery_agent_creates_source_evidence_and_candidate_rows(): void
    {
        Institution::create([
            'name' => 'COMSATS University Islamabad Lahore Campus',
            'slug' => 'comsats-university-islamabad-lahore-campus',
            'status' => 'active',
        ]);

        Http::fake([
            'https://lahore.comsats.edu.pk/academics.aspx' => Http::response(
                '<html><body>
                    <a>Bachelor of Science (BS) in Computer Science</a>
                    <a>Bachelor of Science(BS) in Psychology</a>
                    <a>Master of Science (MS) in Computer Science</a>
                </body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $summary = app(InstituteDiscoveryAgentService::class)->run('cui-lahore');

        $this->assertSame(2, $summary['total_rows']);
        $this->assertSame(2, $summary['candidate_rows']);
        $this->assertSame(0, $summary['duplicate_rows']);

        $this->assertDatabaseHas('sources', [
            'id' => $summary['source_id'],
            'url' => 'https://lahore.comsats.edu.pk/academics.aspx',
        ]);

        $this->assertDatabaseHas('evidence', [
            'id' => $summary['evidence_id'],
            'source_id' => $summary['source_id'],
        ]);

        $this->assertDatabaseHas('import_batches', [
            'id' => $summary['batch_id'],
            'entity_type' => 'program',
            'source_type' => 'automated_discovery',
        ]);

        $this->assertSame(2, ImportRow::where('import_batch_id', $summary['batch_id'])->count());
        $this->assertSame(1, ImportBatch::whereKey($summary['batch_id'])->where('status', 'validated')->count());
    }
}

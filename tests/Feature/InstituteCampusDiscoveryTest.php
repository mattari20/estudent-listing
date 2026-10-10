<?php

namespace Tests\Feature;

use App\Models\ImportRow;
use App\Models\Institution;
use App\Services\InstituteDiscoveryAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InstituteCampusDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_campus_profile_creates_non_canonical_candidate_rows_with_provenance(): void
    {
        Institution::create([
            'name' => 'COMSATS University Islamabad Lahore Campus',
            'slug' => 'comsats-university-islamabad-lahore-campus',
            'status' => 'active',
        ]);

        Http::fake([
            'https://lahore.comsats.edu.pk/' => Http::response(
                '<html><body>
                    <a>Lahore Campus</a>
                    <a>Islamabad Campus</a>
                    <a>Campus Life</a>
                </body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $summary = app(InstituteDiscoveryAgentService::class)->run('cui-lahore-campus');

        $this->assertSame('campus', $summary['entity_type']);
        $this->assertSame(2, $summary['total_rows']);
        $this->assertSame(2, $summary['candidate_rows']);

        $rows = ImportRow::where('import_batch_id', $summary['batch_id'])->get();

        $this->assertCount(2, $rows);
        $this->assertTrue($rows->every(fn ($row) => $row->status === 'candidate'));
        $this->assertTrue($rows->every(fn ($row) => data_get($row->payload, '_provenance.evidence_id') === $summary['evidence_id']));
        $this->assertSame('Lahore Campus', $rows->firstWhere('payload.name', 'Lahore Campus')?->payload['name']);
    }
}

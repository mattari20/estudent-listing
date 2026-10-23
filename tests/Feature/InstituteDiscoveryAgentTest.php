<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Services\InstituteDiscoveryAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class InstituteDiscoveryAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_discovery_agent_creates_source_evidence_snapshot_and_candidate_rows(): void
    {
        Storage::fake('local');
        Institution::create([
            'name' => 'COMSATS University Islamabad Lahore Campus',
            'slug' => 'comsats-university-islamabad-lahore-campus',
            'status' => 'active',
        ]);

        $html = '<html><body>
            <a>Bachelor of Science (BS) in Computer Science</a>
            <a>Bachelor of Science(BS) in Psychology</a>
            <a>Master of Science (MS) in Computer Science</a>
        </body></html>';

        Http::fake([
            'https://lahore.comsats.edu.pk/academics.aspx' => Http::response(
                $html,
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $summary = app(InstituteDiscoveryAgentService::class)->run('cui-lahore');

        $this->assertSame(2, $summary['total_rows']);
        $this->assertSame(2, $summary['candidate_rows']);
        $this->assertSame(0, $summary['duplicate_rows']);
        $this->assertSame('created', $summary['review_package']['status']);
        $this->assertSame(2, $summary['review_package']['candidate_count']);

        $this->assertDatabaseHas('source_snapshots', [
            'id' => $summary['snapshot_id'],
            'content_hash' => hash('sha256', $html),
        ]);

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

    public function test_ai_extraction_creates_a_separate_governed_candidate_batch(): void
    {
        config()->set('institute_ai.enabled', true);
        config()->set('institute_ai.api_key', 'test-key');

        Institution::create([
            'name' => 'COMSATS University Islamabad Lahore Campus',
            'slug' => 'comsats-university-islamabad-lahore-campus',
            'status' => 'active',
        ]);

        $html = '<html><body>
            <a>Bachelor of Science (BS) in Computer Science</a>
            <a>Bachelor of Science (BS) in Psychology</a>
        </body></html>';

        Http::fake([
            'https://lahore.comsats.edu.pk/academics.aspx' => Http::response(
                $html,
                200,
                ['Content-Type' => 'text/html']
            ),
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'candidates' => [
                                [
                                    'name' => 'Bachelor of Science (BS) in Computer Science',
                                    'program_level' => 'undergraduate',
                                    'award' => 'BS',
                                    'discipline' => 'Computer Science',
                                    'status' => 'active',
                                    'confidence' => 0.98,
                                    'evidence_excerpt' => 'Bachelor of Science (BS) in Computer Science',
                                ],
                                [
                                    'name' => 'Bachelor of Science (BS) in Psychology',
                                    'program_level' => 'undergraduate',
                                    'award' => 'BS',
                                    'discipline' => 'Psychology',
                                    'status' => 'active',
                                    'confidence' => 0.97,
                                    'evidence_excerpt' => 'Bachelor of Science (BS) in Psychology',
                                ],
                            ],
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $summary = app(InstituteDiscoveryAgentService::class)->run('cui-lahore', true);

        $this->assertSame('ai_extraction', $summary['extraction_method']);
        $this->assertSame(2, $summary['candidate_rows']);

        $this->assertDatabaseHas('import_batches', [
            'id' => $summary['batch_id'],
            'source_type' => 'ai_extraction',
        ]);

        $row = ImportRow::where('import_batch_id', $summary['batch_id'])->firstOrFail();

        $this->assertSame(0.98, (float) data_get($row->payload, '_provenance.confidence'));
        $this->assertSame('ai_extraction', data_get($row->payload, '_provenance.method'));
    }
    public function test_empty_discovery_is_recorded_with_private_no_candidates_package(): void
    {
        Storage::fake('local');
        Institution::create([
            'name' => 'COMSATS University Islamabad Lahore Campus',
            'slug' => 'comsats-university-islamabad-lahore-campus',
            'status' => 'active',
        ]);

        $html = '<html><body><p>No programme listing on this page.</p></body></html>';
        Http::fake([
            'https://lahore.comsats.edu.pk/academics.aspx' => Http::response(
                $html,
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $summary = app(InstituteDiscoveryAgentService::class)->run('cui-lahore');

        $this->assertSame('no_candidates', $summary['outcome']);
        $this->assertSame(0, $summary['total_rows']);
        $this->assertSame(0, $summary['candidate_rows']);
        $this->assertSame('created', $summary['review_package']['status']);

        $batch = ImportBatch::query()->findOrFail($summary['batch_id']);
        $this->assertSame(0, (int) $batch->total_rows);
        $this->assertSame(0, ImportRow::where('import_batch_id', $batch->id)->count());

        $manifest = json_decode(
            Storage::disk('local')->get($summary['review_package']['manifest_path']),
            true
        );
        $this->assertSame('no_candidates', $manifest['status']);
        $this->assertSame(0, $manifest['candidate_count']);
        $this->assertSame($summary['evidence_id'], $manifest['evidence_id']);
        $this->assertSame($summary['snapshot_id'], $manifest['snapshot_id']);
        $this->assertFalse($manifest['canonical_mutations_performed']);
        $this->assertFalse($manifest['automatic_approval_performed']);
    }


    public function test_campus_discovery_refuses_a_campus_level_parent(): void
    {
        Institution::create([
            'name' => 'COMSATS University Islamabad Lahore Campus',
            'slug' => 'comsats-university-islamabad-lahore-campus',
            'institution_type' => 'Public University Campus',
            'status' => 'active',
        ]);

        try {
            app(InstituteDiscoveryAgentService::class)->run('cui-lahore-campus');
            $this->fail('Campus discovery must not use a campus-level record as its own parent.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Campus discovery requires a standalone parent institution. The configured parent appears to be campus-level; resolve the canonical parent institution before running campus discovery.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseMissing('sources', [
            'url' => 'https://lahore.comsats.edu.pk/',
        ]);
        $this->assertDatabaseCount('source_snapshots', 0);
        $this->assertDatabaseCount('import_batches', 0);
    }

}

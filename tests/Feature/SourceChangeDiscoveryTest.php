<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\ImportRow;
use App\Models\Source;
use App\Models\SourceMonitoringPolicy;
use App\Models\SourceSnapshot;
use App\Services\SourceChangeDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourceChangeDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_changed_monitored_snapshot_creates_a_governed_discovery_batch(): void
    {
        $institution = Institution::create([
            'name' => 'Government College University Lahore',
            'slug' => 'government-college-university-lahore',
            'status' => 'active',
        ]);

        $source = Source::create([
            'source_type' => 'official',
            'name' => 'GCU Lahore Official BS Programs',
            'url' => 'https://gcu.edu.pk/admissions.php?pg=bs-program',
            'authority_scope' => 'official institutional academic programme listing',
            'status' => 'active',
        ]);

        $first = '<html><body><a>Bachelor of Science (BS) in Computer Science</a></body></html>';
        $snapshot = SourceSnapshot::create([
            'source_id' => $source->id,
            'reference' => $source->url,
            'content_hash' => hash('sha256', $first),
            'content_length' => strlen($first),
            'content_type' => 'text/html',
            'content' => $first,
            'captured_at' => now(),
            'status' => 'captured',
            'metadata' => [],
        ]);

        SourceMonitoringPolicy::create([
            'name' => 'Automated discovery — gcu-lahore-programs',
            'subject_type' => 'program',
            'attribute' => 'automated_discovery',
            'fresh_after_days' => 7,
            'stale_after_days' => 30,
            'priority' => 'normal',
            'status' => 'active',
            'source_id' => $source->id,
            'cadence' => 'weekly',
        ]);

        $second = '<html><body><a>Bachelor of Science (BS) in Computer Science</a><a>Bachelor of Science (BS) in Psychology</a></body></html>';
        $changed = SourceSnapshot::create([
            'source_id' => $source->id,
            'reference' => $source->url,
            'content_hash' => hash('sha256', $second),
            'content_length' => strlen($second),
            'content_type' => 'text/html',
            'content' => $second,
            'captured_at' => now()->addMinute(),
            'status' => 'captured',
            'metadata' => [],
        ]);

        $observation = \App\Models\SourceChangeObservation::create([
            'source_id' => $source->id,
            'previous_snapshot_id' => $snapshot->id,
            'current_snapshot_id' => $changed->id,
            'change_state' => 'changed',
            'materiality' => 'unknown',
            'status' => 'detected',
            'summary' => 'Test change',
            'observed_at' => now(),
        ]);

        $summary = app(SourceChangeDiscoveryService::class)->process($observation);

        $this->assertSame(2, $summary['total_rows']);
        $this->assertDatabaseHas('import_batches', [
            'id' => $summary['batch_id'],
            'source_type' => 'automated_discovery',
        ]);
        $this->assertSame(2, ImportRow::where('import_batch_id', $summary['batch_id'])->count());
        $this->assertSame($institution->id, ImportRow::where('import_batch_id', $summary['batch_id'])->value('parent_subject_id'));
    }
}

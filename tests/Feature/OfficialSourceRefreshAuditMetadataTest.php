<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Institution;
use App\Models\OfficialSourceRefreshEvent;
use App\Services\OfficialSourceCanonicalRefreshService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OfficialSourceRefreshAuditMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_event_preserves_source_references_and_evidence_metadata(): void
    {
        $institution = Institution::create([
            'name' => 'Refresh Guard University',
            'slug' => 'refresh-guard-university',
            'status' => 'active',
        ]);
        $campus = Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Old Campus Name',
            'slug' => 'old-campus-name',
            'status' => 'active',
        ]);

        $evidence = $this->validEvidence();
        $evidence['source_id'] = 42;
        $evidence['source_change_observation_id'] = 84;
        $evidence['observed_at'] = '2026-10-25T04:00:00+00:00';

        app(OfficialSourceCanonicalRefreshService::class)->refresh(
            $campus, 'name', 'Old Campus Name', 'Updated Campus Name', $evidence
        );

        $event = OfficialSourceRefreshEvent::query()->firstOrFail();
        $this->assertSame(42, (int) $event->source_id);
        $this->assertSame(84, (int) $event->source_change_observation_id);
        $this->assertSame(Campus::class, $event->canonical_type);
        $this->assertSame((int) $campus->id, (int) $event->canonical_id);
        $this->assertSame('https://university.example.edu/campuses', $event->evidence_url);
        $this->assertSame('The campus is now called Updated Campus Name.', $event->evidence_excerpt);
        $this->assertSame(hash('sha256', 'official campus page'), $event->content_fingerprint);
        $this->assertNotNull($event->observed_at);
        $this->assertNotNull($event->occurred_at);
    }

    private function validEvidence(): array
{
    return [
        'source_id' => null,
        'source_change_observation_id' => null,
        'source_official' => true,
        'source_identity_verified' => true,
        'fetch_successful' => true,
        'entity_match_confirmed' => true,
        'evidence_url' => 'https://university.example.edu/campuses',
        'evidence_excerpt' => 'The campus is now called Updated Campus Name.',
        'observed_at' => '2026-10-25T04:00:00Z',
        'content_fingerprint' => hash('sha256', 'official campus page'),
    ];
}

}

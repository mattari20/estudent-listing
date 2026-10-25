<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Institution;
use App\Models\OfficialSourceRefreshEvent;
use App\Services\OfficialSourceCanonicalRefreshService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OfficialSourceRefreshAmbiguousIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_ambiguous_entity_identity_cannot_change_canonical_data_or_write_audit(): void
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
        $evidence['entity_identity_ambiguous'] = true;

        try {
            app(OfficialSourceCanonicalRefreshService::class)->refresh(
                $campus, 'name', 'Old Campus Name', 'Potentially Wrong Campus', $evidence
            );
            $this->fail('Ambiguous entity identity must block refresh.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('ambiguous_entity_identity', $exception->getMessage());
        }

        $this->assertSame('Old Campus Name', $campus->fresh()->name);
        $this->assertDatabaseCount('official_source_refresh_events', 0);
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

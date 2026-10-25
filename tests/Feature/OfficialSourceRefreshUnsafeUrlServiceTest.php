<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Institution;
use App\Services\OfficialSourceCanonicalRefreshService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class OfficialSourceRefreshUnsafeUrlServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_credential_bearing_evidence_url_blocks_refresh_without_writes(): void
    {
        $institution = Institution::create([
            'name' => 'Refresh Safety University',
            'slug' => 'refresh-safety-university',
            'status' => 'active',
        ]);
        $campus = Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Current Campus Name',
            'slug' => 'current-campus-name',
            'status' => 'active',
        ]);
        $evidence = $this->validEvidence();
        $evidence['evidence_url'] = 'https://reviewer:secret@university.example.edu/campuses';

        try {
            app(OfficialSourceCanonicalRefreshService::class)->refresh(
                $campus, 'name', 'Current Campus Name', 'Updated Campus Name', $evidence
            );
            $this->fail('Credential-bearing evidence URLs must be blocked.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('valid_field_evidence_url_required', $exception->getMessage());
        }

        $this->assertSame('Current Campus Name', $campus->fresh()->name);
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

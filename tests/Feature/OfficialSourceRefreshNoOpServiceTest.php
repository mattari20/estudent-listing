<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Institution;
use App\Services\OfficialSourceCanonicalRefreshService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class OfficialSourceRefreshNoOpServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_op_proposal_is_rejected_without_canonical_or_audit_writes(): void
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

        try {
            app(OfficialSourceCanonicalRefreshService::class)->refresh(
                $campus, 'name', 'Current Campus Name', 'Current Campus Name', $this->validEvidence()
            );
            $this->fail('No-op proposal must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('no_material_field_change', $exception->getMessage());
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

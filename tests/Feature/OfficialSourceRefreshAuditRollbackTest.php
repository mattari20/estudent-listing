<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Institution;
use App\Services\OfficialSourceCanonicalRefreshService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class OfficialSourceRefreshAuditRollbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_update_rolls_back_if_audit_event_cannot_be_written(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->markTestSkipped('This failure-injection test uses a SQLite trigger.');
        }

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

        DB::statement("CREATE TRIGGER fail_official_refresh_audit BEFORE INSERT ON official_source_refresh_events BEGIN SELECT RAISE(ABORT, 'simulated audit insert failure'); END");

        try {
            app(OfficialSourceCanonicalRefreshService::class)->refresh(
                $campus, 'name', 'Current Campus Name', 'Updated Campus Name', $this->validEvidence()
            );
            $this->fail('The simulated audit failure should abort the refresh.');
        } catch (\Throwable $exception) {
            $this->assertStringContainsString('simulated audit insert failure', $exception->getMessage());
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
